// journal_entries.js — Journal Entries Vue app

const { createApp } = Vue;

const emptyLine = () => ({ account_code: '', account_name: '', description: '', debit_amount: '', credit_amount: '' });

createApp({
  data() {
    return {
      systemName:       SYSTEM_NAME,
      title:            '',
      loading:          false,
      saving:           false,
      saved:            false,
      error:            '',
      viewMode:         false,
      isExisting:       false,
      posting:          false,
      pickerLineIndex:  null,
      coaAccounts:           [],
      suggestions:           [],
      activeInputLine:       null,
      activeSuggestionIndex: -1,
      form: {
        ref:          '',
        journal_date: '',
        period:       '',
        description:  '',
        reference_doc:'',
        status:       'draft',
        drafted_by:   '',
        drafted_at:   '',
        posted_by:    '',
        posted_at:    '',
        lines:        [emptyLine(), emptyLine()],
      },
    };
  },

  computed: {
    totalDebit() {
      return this.form.lines.reduce((s, l) => s + (parseFloat(l.debit_amount) || 0), 0);
    },
    totalCredit() {
      return this.form.lines.reduce((s, l) => s + (parseFloat(l.credit_amount) || 0), 0);
    },
    isBalanced() {
      return this.totalDebit > 0 && Math.abs(this.totalDebit - this.totalCredit) < 0.001;
    },
    isDirty() {
      return this.form.description.trim() !== '' || this.form.journal_date !== '';
    },

    isReadyToSave() {
      return this.validationHints.length > 0 && this.validationHints.every(h => h.ok);
    },

    validationHints() {
      const a = (v) => parseFloat(v) || 0;
      const hints = [];

      hints.push({ text: 'Journal date is required',        ok: this.form.journal_date !== '' });
      hints.push({ text: 'Description / narration is required', ok: this.form.description.trim() !== '' });

      const nonEmpty = this.form.lines.filter(l =>
        l.account_code.trim() !== '' || a(l.debit_amount) > 0 || a(l.credit_amount) > 0
      );
      const partial = nonEmpty.filter(l =>
        l.account_code.trim() === '' || a(l.debit_amount) + a(l.credit_amount) === 0
      );
      const complete = nonEmpty.filter(l =>
        l.account_code.trim() !== '' && a(l.debit_amount) + a(l.credit_amount) > 0
      );

      if (partial.length > 0) {
        hints.push({
          text: partial.length + ' line' + (partial.length > 1 ? 's are' : ' is') + ' incomplete — each line needs an account code and a debit or credit amount',
          ok: false,
        });
      }

      hints.push({
        text: 'At least 2 complete lines are required (have ' + complete.length + ')',
        ok: complete.length >= 2,
      });

      if (complete.length >= 2) {
        const diff = Math.abs(this.totalDebit - this.totalCredit);
        hints.push({
          text: this.isBalanced
            ? 'Entry is balanced — total ' + this.fmtNum(this.totalDebit)
            : 'Entry does not balance — difference: ' + this.fmtNum(diff),
          ok: this.isBalanced,
        });
      }

      return hints;
    },
  },

  mounted() {
    this.fetchRefNumber();
    this.loadCoaAccounts();
    setTimeout(() => {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
      });
    }, 300);
    document.addEventListener('journal_entries-selected', (e) => this.loadEntry(e.detail));
    document.addEventListener('coa-line-selected', (e) => {
      if (this.pickerLineIndex === null) return;
      const line = this.form.lines[this.pickerLineIndex];
      if (!line) return;
      line.account_code = e.detail.account_code;
      line.account_name = e.detail.account_name;
      this.pickerLineIndex = null;
    });
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(() => { this.error = 'Failed to load reference number.'; })
        .finally(() => { this.loading = false; });
    },

    syncPeriod() {
      if (this.form.journal_date) {
        this.form.period = this.form.journal_date.substring(0, 7);
      }
    },

    addLine()         { this.form.lines.push(emptyLine()); },
    removeLine(i)     { if (this.form.lines.length > 2) this.form.lines.splice(i, 1); },
    fmtNum(v)      { return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    fmtDateTime(v) {
      if (!v) return '';
      const d = new Date(v);
      return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) +
             ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    },

    openPicker(i) {
      this.pickerLineIndex = i;
      new bootstrap.Modal(document.getElementById('coaPickerModal')).show();
    },

    loadCoaAccounts() {
      axios.get('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=list')
        .then(res => { this.coaAccounts = res.data || []; })
        .catch(() => {});
    },

    onCodeInput(i) {
      const val = (this.form.lines[i].account_code || '').trim();
      this.activeInputLine = i;
      if (val.length < 2) {
        this.form.lines[i].account_name = '';
        this.suggestions           = [];
        this.activeSuggestionIndex = -1;
        return;
      }
      const lower = val.toLowerCase();
      const exact = this.coaAccounts.find(a => a.account_code.toLowerCase() === lower);
      if (exact) {
        this.form.lines[i].account_name = exact.account_name;
        this.suggestions           = [];
        this.activeInputLine       = null;
        this.activeSuggestionIndex = -1;
        return;
      }
      this.form.lines[i].account_name = '';
      this.suggestions           = this.coaAccounts.filter(a =>
        a.account_code.toLowerCase().startsWith(lower) ||
        a.account_name.toLowerCase().includes(lower)
      ).slice(0, 10);
      this.activeSuggestionIndex = -1;
    },

    pickSuggestion(i, s) {
      this.form.lines[i].account_code = s.account_code;
      this.form.lines[i].account_name = s.account_name;
      this.suggestions           = [];
      this.activeInputLine       = null;
      this.activeSuggestionIndex = -1;
    },

    closeSuggestions() {
      setTimeout(() => {
        this.suggestions           = [];
        this.activeInputLine       = null;
        this.activeSuggestionIndex = -1;
      }, 150);
    },

    onCodeKeyDown(dir) {
      if (!this.suggestions.length) return;
      if (dir === 'down') {
        this.activeSuggestionIndex = Math.min(this.activeSuggestionIndex + 1, this.suggestions.length - 1);
      } else {
        this.activeSuggestionIndex = Math.max(this.activeSuggestionIndex - 1, 0);
      }
    },

    onCodeKeyEnter(i) {
      if (this.suggestions.length && this.activeSuggestionIndex >= 0) {
        this.pickSuggestion(i, this.suggestions[this.activeSuggestionIndex]);
        return;
      }
      const next = i + 1;
      if (next >= this.form.lines.length) this.form.lines.push(emptyLine());
      this.$nextTick(() => {
        const inputs = document.querySelectorAll('.je-code');
        if (inputs[next]) inputs[next].focus();
      });
    },

    lineErrors(line) {
      const hasCode = line.account_code.trim() !== '';
      const hasAmt  = (parseFloat(line.debit_amount) || 0) + (parseFloat(line.credit_amount) || 0) > 0;
      if (!hasCode && !hasAmt) return {};
      return { code: !hasCode, amount: !hasAmt };
    },

    onDebitInput(i) {
      if ((parseFloat(this.form.lines[i].debit_amount) || 0) > 0) {
        this.form.lines[i].credit_amount = '';
      }
    },

    onCreditInput(i) {
      if ((parseFloat(this.form.lines[i].credit_amount) || 0) > 0) {
        this.form.lines[i].debit_amount = '';
      }
    },

    onDebitEnter(i) {
      const next = i + 1;
      if (next >= this.form.lines.length) this.form.lines.push(emptyLine());
      this.$nextTick(() => {
        const inputs = document.querySelectorAll('.je-debit');
        if (inputs[next]) inputs[next].focus();
      });
    },

    onCreditEnter(i) {
      const next = i + 1;
      if (next >= this.form.lines.length) this.form.lines.push(emptyLine());
      this.$nextTick(() => {
        const inputs = document.querySelectorAll('.je-credit');
        if (inputs[next]) inputs[next].focus();
      });
    },

    loadEntry(data) {
      this.form.ref           = data.ref;
      this.form.journal_date  = data.journal_date;
      this.form.period        = data.period;
      this.form.description   = data.description;
      this.form.reference_doc = data.reference_doc ?? '';
      this.form.status        = data.status;
      this.form.drafted_by    = data.drafted_by  ?? '';
      this.form.drafted_at    = data.drafted_at  ?? '';
      this.form.posted_by     = data.posted_by   ?? '';
      this.form.posted_at     = data.posted_at   ?? '';
      this.form.lines         = (data.lines && data.lines.length)
        ? data.lines.map(l => ({
            account_code:  l.account_code  ?? '',
            account_name:  l.account_name  ?? '',
            description:   l.description   ?? '',
            debit_amount:  l.debit_amount  ?? '',
            credit_amount: l.credit_amount ?? '',
          }))
        : [emptyLine(), emptyLine()];
      this.viewMode   = true;
      this.isExisting = true;
      this.saved      = false;
      this.error      = '';
    },

    onPost() {
      this.posting = true;
      this.error   = '';
      axios.post('/modules/finance/accounting/journal_entries/journal_entries_data.php?action=post&ref=' + encodeURIComponent(this.form.ref))
        .then(() => {
          this.form.status    = 'posted';
          this.form.posted_by = '';
          this.form.posted_at = new Date().toISOString();
          axios.get('/modules/finance/accounting/journal_entries/journal_entries_data.php?action=get&ref=' + encodeURIComponent(this.form.ref))
            .then(res => {
              this.form.posted_by = res.data.posted_by ?? '';
              this.form.posted_at = res.data.posted_at ?? '';
            });
        })
        .catch(err => { this.error = err.response?.data?.error || 'Failed to post entry.'; })
        .finally(() => { this.posting = false; });
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/finance/accounting/journal_entries/journal_entries_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) { this.error = 'No saved record found. Please search and select a record to print.'; return; }
          const params = new URLSearchParams({
            ref:           this.form.ref,
            journal_date:  this.form.journal_date,
            period:        this.form.period,
            description:   this.form.description,
            reference_doc: this.form.reference_doc,
            status:        this.form.status,
            total_debit:   this.totalDebit,
            total_credit:  this.totalCredit,
            lines:         JSON.stringify(this.form.lines),
          });
          window.open('/modules/finance/accounting/journal_entries/journal_entries_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.journal_date  = '';
      this.form.period        = '';
      this.form.description   = '';
      this.form.reference_doc = '';
      this.form.status        = 'draft';
      this.form.drafted_by    = '';
      this.form.drafted_at    = '';
      this.form.posted_by     = '';
      this.form.posted_at     = '';
      this.form.lines         = [emptyLine(), emptyLine()];
      this.viewMode           = false;
      this.isExisting         = false;
      this.posting            = false;
      this.saved              = false;
      this.error              = '';
      this.suggestions        = [];
      this.activeInputLine    = null;
      this.activeSuggestionIndex = -1;
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.journal_date === '')       { this.error = 'Journal date is required.'; return; }
      if (this.form.description.trim() === '') { this.error = 'Description is required.'; return; }
      const amt = (v) => (parseFloat(v) || 0);
      const nonEmpty = this.form.lines.filter(l =>
        l.account_code.trim() !== '' || amt(l.debit_amount) > 0 || amt(l.credit_amount) > 0
      );
      const partial = nonEmpty.filter(l =>
        l.account_code.trim() === '' || (amt(l.debit_amount) + amt(l.credit_amount) === 0)
      );
      if (partial.length > 0) { this.error = 'Each line must have an account code and either a debit or credit amount.'; return; }
      const validLines = nonEmpty;
      if (validLines.length < 2) { this.error = 'At least two lines with account codes are required.'; return; }
      if (!this.isBalanced)      { this.error = 'Journal entry must balance (total debits must equal total credits).'; return; }

      // A loaded entry can only be resaved (i.e. updated) while it's still a
      // draft — once posted, a journal entry is immutable for audit/accounting
      // integrity. The form is already read-only for any loaded entry (see
      // :disabled="viewMode" in journal_entries.php), so this is a defensive
      // backstop, not something reachable via the current UI.
      if (this.isExisting && this.form.status !== 'draft') {
        this.error = 'Only draft entries can be edited.';
        return;
      }

      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const payload = { ...this.form, lines: validLines };

      const proceed = (action) => {
        axios.post('/modules/finance/accounting/journal_entries/journal_entries_data.php?action=' + action, payload)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save journal entry.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/finance/accounting/journal_entries/journal_entries_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },
  },
}).mount('#je-app');
