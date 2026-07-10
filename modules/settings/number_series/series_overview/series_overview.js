// series_overview.js — Series Overview Vue app
// Edits rows in sys_tms_module_number_series (the real reference-generation
// engine, see server/general/number_series.php). Rows are provisioned per
// module elsewhere — this screen can only view/edit existing ones.

const { createApp } = Vue;

function blankForm() {
  return {
    id:             '',
    ref:            '',
    module_ref:     '',
    module_name:    '',
    prefix:         '',
    suffix:         '',
    padding_length: 7,
    separator:      '-',
    reset_period:   'never',
    current_number: 0,
    record_status:  '',
  };
}

createApp({
  data() {
    return {
      saving: false,
      saved:  false,
      error:  '',
      form:   blankForm(),
    };
  },

  computed: {
    preview() {
      const num = String((this.form.current_number || 0) + 1).padStart(this.form.padding_length || 7, '0');
      return (this.form.prefix || '') + (this.form.separator || '') + num + (this.form.suffix || '');
    },
  },

  mounted() {
    document.addEventListener('nsr-selected', (e) => this.loadRecord(e.detail));
  },

  methods: {
    loadRecord(data) {
      this.form = {
        id:             data.id,
        ref:            data.ref,
        module_ref:     data.module_ref,
        module_name:    data.module_name,
        prefix:         data.prefix         ?? '',
        suffix:         data.suffix         ?? '',
        padding_length: data.padding_length ?? 7,
        separator:      data.separator      ?? '-',
        reset_period:   data.reset_period   ?? 'never',
        current_number: data.current_number ?? 0,
        record_status:  data.record_status  ?? '',
      };
      this.saved = false;
      this.error = '';
    },

    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.id) { this.error = 'No record to print. Search and select a series first.'; return; }
      const params = new URLSearchParams({
        ref:            this.form.ref,
        module_name:    this.form.module_name,
        prefix:         this.form.prefix,
        suffix:         this.form.suffix,
        padding_length: this.form.padding_length,
        separator:      this.form.separator,
        reset_period:   this.form.reset_period,
        current_number: this.form.current_number,
        record_status:  this.form.record_status,
      });
      window.open('/modules/settings/number_series/series_overview/series_overview_print.php?' + params.toString(), '_blank');
    },

    onReset() {
      this.form  = blankForm();
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.form.id) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      axios.post('/modules/settings/number_series/series_overview/series_overview_data.php?action=save', this.form)
        .then(() => { this.saved = true; })
        .catch(() => { this.error = 'Failed to save number series.'; })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#nsr-app');
