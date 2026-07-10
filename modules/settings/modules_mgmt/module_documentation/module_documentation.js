// module_documentation.js — Module Documentation editor

const { createApp } = Vue;

const BLANK_FORM = {
  title: '',
  subtitle: '',
  purpose: '',
  business_problem: '',
  business_impact: '',
  when_to_use: '',
  workflow: '',
  prerequisites: '',
  best_practices: '',
  notes: '',
};

const DATA_URL = '/modules/settings/modules_mgmt/module_documentation/module_documentation_data.php';

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title: '',
      sections: [],
      subsections: [],
      modules: [],
      loadingLookups: false,
      loadingRecord: false,
      selectedSectionRef: '',
      selectedSubsectionRef: '',
      selectedModuleRef: '',
      form: { ...BLANK_FORM },
      saving: false,
      saved: false,
      error: '',
      recordRequestId: 0,
    };
  },

  computed: {
    filteredSubsections() {
      return this.subsections.filter(s => s.section_ref === this.selectedSectionRef);
    },
    filteredModules() {
      return this.modules.filter(m => m.subsection_ref === this.selectedSubsectionRef);
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
        .catch(() => { this.title = 'Module Documentation'; });
    },

    loadLookups() {
      this.loadingLookups = true;
      Promise.all([
        axios.get(DATA_URL, { params: { action: 'list_sections' } }),
        axios.get(DATA_URL, { params: { action: 'list_subsections' } }),
        axios.get(DATA_URL, { params: { action: 'list_modules' } }),
      ])
        .then(([sectionsRes, subsectionsRes, modulesRes]) => {
          this.sections = sectionsRes.data;
          this.subsections = subsectionsRes.data;
          this.modules = modulesRes.data;
        })
        .catch(() => { this.error = 'Failed to load section/module lists.'; })
        .finally(() => { this.loadingLookups = false; });
    },

    onSectionChange() {
      this.selectedSubsectionRef = '';
      this.selectedModuleRef = '';
      this.form = { ...BLANK_FORM };
      this.saved = false;
      this.error = '';
    },

    onSubsectionChange() {
      this.selectedModuleRef = '';
      this.form = { ...BLANK_FORM };
      this.saved = false;
      this.error = '';
    },

    onModuleChange() {
      this.saved = false;
      this.error = '';
      if (!this.selectedModuleRef) { this.form = { ...BLANK_FORM }; return; }

      const requestId = ++this.recordRequestId;
      this.loadingRecord = true;
      axios.get(DATA_URL, { params: { action: 'get', module_ref: this.selectedModuleRef } })
        .then(res => {
          if (requestId !== this.recordRequestId) return; // a newer selection has since superseded this response
          this.form = { ...BLANK_FORM, ...(res.data || {}) };
        })
        .catch(() => {
          if (requestId !== this.recordRequestId) return;
          this.error = 'Failed to load documentation for this module.';
        })
        .finally(() => {
          if (requestId === this.recordRequestId) this.loadingRecord = false;
        });
    },

    onSave() {
      if (!this.selectedModuleRef) return;
      this.saving = true;
      this.saved = false;
      this.error = '';
      axios.post(DATA_URL + '?action=save', { module_ref: this.selectedModuleRef, ...this.form })
        .then(() => { this.saved = true; })
        .catch(() => { this.error = 'Failed to save documentation.'; })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#mdoc-app');
