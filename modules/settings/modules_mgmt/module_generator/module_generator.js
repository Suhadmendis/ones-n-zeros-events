// module_generator.js — Generate Module tool

const { createApp } = Vue;

const DATA_URL = '/modules/settings/modules_mgmt/module_generator/module_generator_data.php';

const RESERVED_COLUMNS = ['id', 'ref', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by'];
const RESERVED_LINE_COLUMNS = ['id', 'ref', 'line_no', 'created_at', 'updated_at', 'created_by', 'updated_by'];

function slugify(name) {
  return (name || '').trim().toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .replace(/_{2,}/g, '_')
    .slice(0, 50);
}

function derivePrefix(name) {
  const words = (name || '').trim().split(/\s+/).filter(Boolean);
  if (!words.length) return '';
  const raw = words.length > 1
    ? words.slice(0, 4).map(w => w[0]).join('')
    : words[0].slice(0, 3);
  return raw.toUpperCase().replace(/[^A-Z0-9]/g, '');
}

function newField(reservedList) {
  return { _id: null, label: '', column: '', columnTouched: false, type: 'text', required: false, options: '', _reserved: reservedList };
}

function fieldErrorsFor(list, reserved) {
  const counts = {};
  list.forEach(f => { if (f.column) counts[f.column] = (counts[f.column] || 0) + 1; });

  const errors = {};
  list.forEach(f => {
    if (!f.label) { errors[f._id] = 'Label is required.'; return; }
    if (!f.column || !/^[a-z][a-z0-9_]{1,49}$/.test(f.column)) {
      errors[f._id] = 'Column must start with a letter: lowercase letters, numbers, underscores only.';
      return;
    }
    if (reserved.includes(f.column)) {
      errors[f._id] = `"${f.column}" is a reserved column name.`;
      return;
    }
    if (counts[f.column] > 1) {
      errors[f._id] = 'Duplicate column name.';
      return;
    }
    if (f.type === 'dropdown') {
      const opts = (f.options || '').split(',').map(s => s.trim()).filter(Boolean);
      if (opts.length < 2) {
        errors[f._id] = 'Dropdown needs at least 2 comma-separated options.';
      }
    }
  });
  return errors;
}

function serializeFields(list) {
  return list.map(f => ({
    label:    f.label,
    column:   f.column,
    type:     f.type,
    required: !!f.required,
    options:  f.type === 'dropdown' ? (f.options || '').split(',').map(s => s.trim()).filter(Boolean) : [],
  }));
}

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',

      sections:    [],
      subsections: [],
      loadingLookups: false,

      selectedSectionRef:    '',
      selectedSubsectionRef: '',
      moduleName:            '',
      moduleType:            'entry', // entry | header_detail | report

      slugCheck: { status: 'idle', slug: '' }, // idle|checking|available|taken|invalid
      _slugCheckTimer: null,
      _slugCheckSeq:   0,

      tableName:        '',
      tableNameTouched: false,
      tableCheck: { status: 'idle', table: '' },
      _tableCheckTimer: null,
      _tableCheckSeq:   0,

      refPrefix:        '',
      refPrefixTouched: false,

      fields:    [],
      _fieldSeq: 0,

      lineFields:    [],
      _lineFieldSeq: 0,

      createsJournalEntry: false,
      glAmountField:       '',
      glLines:    [],
      _glLineSeq: 0,

      generating: false,
      generated:  null,
      error:      '',
    };
  },

  computed: {
    filteredSubsections() {
      return this.subsections.filter(s => s.section_ref === this.selectedSectionRef);
    },
    slugPreview() {
      return slugify(this.moduleName);
    },
    refPrefixValid() {
      return /^[A-Z0-9]{2,6}$/.test(this.refPrefix);
    },
    fieldErrors() {
      return fieldErrorsFor(this.fields, RESERVED_COLUMNS);
    },
    lineFieldErrors() {
      // Line fields land in their own child table, so they only need to avoid
      // the line-table's own reserved columns — not the header field's columns.
      return fieldErrorsFor(this.lineFields, RESERVED_LINE_COLUMNS);
    },
    numericFields() {
      return this.fields.filter(f => f.type === 'number' || f.type === 'decimal');
    },
    glLinesError() {
      if (!this.createsJournalEntry) return '';
      if (this.glLines.length < 2) return 'At least two posting lines are required (one debit, one credit).';
      const hasDebit  = this.glLines.some(l => l.entry_type === 'debit');
      const hasCredit = this.glLines.some(l => l.entry_type === 'credit');
      if (!hasDebit || !hasCredit) return 'Posting lines must include at least one debit and one credit line.';
      if (this.glLines.some(l => !l.account_code.trim())) return 'Every posting line needs an account code.';
      return '';
    },
    canGenerate() {
      const baseOk = !!this.selectedSectionRef && !!this.selectedSubsectionRef &&
             this.slugCheck.status === 'available' && this.slugCheck.slug === this.slugPreview;

      if (!baseOk) return false;
      if (this.moduleType === 'report') return true;

      const tableOk = this.tableCheck.status === 'available' && this.tableCheck.table === this.tableName &&
             this.refPrefixValid &&
             Object.keys(this.fieldErrors).length === 0;
      if (!tableOk) return false;

      if (this.moduleType === 'header_detail' && Object.keys(this.lineFieldErrors).length !== 0) return false;

      if (this.createsJournalEntry) {
        if (!this.glAmountField) return false;
        if (this.glLinesError) return false;
      }

      return true;
    },
  },

  mounted() {
    this.fetchTitle();
    this.loadLookups();
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => { this.title = 'Generate Module'; });
    },

    loadLookups() {
      this.loadingLookups = true;
      Promise.all([
        axios.get(DATA_URL, { params: { action: 'list_sections' } }),
        axios.get(DATA_URL, { params: { action: 'list_subsections' } }),
      ])
        .then(([sectionsRes, subsectionsRes]) => {
          this.sections    = sectionsRes.data;
          this.subsections = subsectionsRes.data;
        })
        .catch(() => { this.error = 'Failed to load section/subsection lists.'; })
        .finally(() => { this.loadingLookups = false; });
    },

    onSectionChange() {
      this.selectedSubsectionRef = '';
      this.generated = null;
      this.error = '';
    },

    onSubsectionChange() {
      this.generated = null;
      this.error = '';
    },

    onModuleNameInput() {
      this.generated = null;
      this.error = '';

      const slug = this.slugPreview;
      if (!this.tableNameTouched) this.tableName = slug ? `m_${slug}` : '';
      if (!this.refPrefixTouched) this.refPrefix = derivePrefix(this.moduleName);

      clearTimeout(this._slugCheckTimer);
      if (slug.length < 3) {
        this.slugCheck = { status: 'invalid', slug };
      } else {
        this.slugCheck = { status: 'checking', slug };
        this._slugCheckTimer = setTimeout(() => this.checkSlug(slug), 400);
      }

      this.scheduleTableCheck();
    },

    checkSlug(slug) {
      const seq = ++this._slugCheckSeq;
      axios.get(DATA_URL, { params: { action: 'check_slug', folder: slug } })
        .then(res => {
          if (seq !== this._slugCheckSeq) return; // a newer keystroke superseded this check
          this.slugCheck = { status: res.data.available ? 'available' : 'taken', slug };
        })
        .catch(() => {
          if (seq !== this._slugCheckSeq) return;
          this.slugCheck = { status: 'invalid', slug };
        });
    },

    onTableNameInput() {
      this.tableNameTouched = true;
      this.generated = null;
      this.error = '';
      this.scheduleTableCheck();
    },

    scheduleTableCheck() {
      clearTimeout(this._tableCheckTimer);
      const table = this.tableName;
      if (!/^m_[a-z][a-z0-9_]{2,58}$/.test(table)) {
        this.tableCheck = { status: 'invalid', table };
        return;
      }
      this.tableCheck = { status: 'checking', table };
      this._tableCheckTimer = setTimeout(() => this.checkTable(table), 400);
    },

    checkTable(table) {
      const seq = ++this._tableCheckSeq;
      axios.get(DATA_URL, { params: { action: 'check_table', table } })
        .then(res => {
          if (seq !== this._tableCheckSeq) return;
          this.tableCheck = { status: res.data.available ? 'available' : 'taken', table };
        })
        .catch(() => {
          if (seq !== this._tableCheckSeq) return;
          this.tableCheck = { status: 'invalid', table };
        });
    },

    onRefPrefixInput() {
      this.refPrefixTouched = true;
      this.refPrefix = this.refPrefix.toUpperCase();
      this.generated = null;
      this.error = '';
    },

    addField() {
      this.fields.push({ ...newField(RESERVED_COLUMNS), _id: ++this._fieldSeq });
    },
    removeField(idx) {
      const [removed] = this.fields.splice(idx, 1);
      if (removed && removed.column === this.glAmountField) this.glAmountField = '';
    },
    onFieldLabelInput(f) {
      if (!f.columnTouched) f.column = slugify(f.label);
    },

    addLineField() {
      this.lineFields.push({ ...newField(RESERVED_LINE_COLUMNS), _id: ++this._lineFieldSeq });
    },
    removeLineField(idx) {
      this.lineFields.splice(idx, 1);
    },
    onLineFieldLabelInput(f) {
      if (!f.columnTouched) f.column = slugify(f.label);
    },

    addGlLine() {
      this.glLines.push({ _id: ++this._glLineSeq, entry_type: this.glLines.length % 2 === 0 ? 'debit' : 'credit', account_code: '', account_name: '', description: '' });
    },
    removeGlLine(idx) {
      this.glLines.splice(idx, 1);
    },

    onGenerate() {
      if (!this.canGenerate) return;
      this.generating = true;
      this.generated  = null;
      this.error      = '';

      const payload = {
        section_ref:    this.selectedSectionRef,
        subsection_ref: this.selectedSubsectionRef,
        module_name:    this.moduleName,
        module_type:    this.moduleType,
      };

      if (this.moduleType !== 'report') {
        payload.table_name = this.tableName;
        payload.ref_prefix = this.refPrefix;
        payload.fields = serializeFields(this.fields);

        if (this.moduleType === 'header_detail') {
          payload.line_fields = serializeFields(this.lineFields);
        }

        payload.creates_journal_entry = this.createsJournalEntry;
        if (this.createsJournalEntry) {
          payload.gl_amount_field = this.glAmountField;
          payload.gl_lines = this.glLines.map(l => ({
            entry_type:   l.entry_type,
            account_code: l.account_code,
            account_name: l.account_name,
            description:  l.description,
          }));
        }
      }

      axios.post(DATA_URL + '?action=generate', payload)
        .then(res => {
          this.generated = res.data;
          this.moduleName       = '';
          this.moduleType        = 'entry';
          this.tableName        = '';
          this.tableNameTouched = false;
          this.refPrefix        = '';
          this.refPrefixTouched = false;
          this.fields            = [];
          this.lineFields        = [];
          this.createsJournalEntry = false;
          this.glAmountField     = '';
          this.glLines            = [];
          this.slugCheck  = { status: 'idle', slug: '' };
          this.tableCheck = { status: 'idle', table: '' };
        })
        .catch(err => {
          this.error = err.response?.data?.error || 'Failed to generate module.';
        })
        .finally(() => { this.generating = false; });
    },
  },
}).mount('#mgen-app');
