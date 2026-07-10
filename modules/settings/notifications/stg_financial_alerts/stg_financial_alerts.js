// stg_financial_alerts.js — Financial Alerts rule matrix Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:             '',
    alert_name:      '',
    condition_field: '',
    operator:        '>',
    threshold_value: '',
    channel:         'email',
    severity:        'medium',
    is_enabled:      true,
    description:     '',
    status:          'active',
  });

  createApp({
    data() {
      return {
        title:    'Financial Alerts',
        form:     emptyForm(),
        snapshot: null,
        mode:     'view',
        loading:  false,
        saving:   false,
        error:    '',
        saved:    false,
      };
    },

    computed: {
      isDirty() {
        return JSON.stringify(this.form) !== JSON.stringify(this.snapshot);
      },
    },

    methods: {
      async fetchNextRef() {
        this.loading = true;
        try {
          const r = await fetch('/modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_data.php?action=next_ref');
          const d = await r.json();
          this.form.ref = d.ref ?? '';
        } finally {
          this.loading = false;
        }
      },

      onAdd() {
        this.form     = emptyForm();
        this.snapshot = null;
        this.error    = '';
        this.saved    = false;
        this.mode     = 'add';
        this.fetchNextRef();
        this.snapshot = JSON.parse(JSON.stringify(this.form));
      },

      onEdit() {
        if (!this.form.ref) return;
        this.mode  = 'edit';
        this.error = '';
        this.saved = false;
        this.snapshot = JSON.parse(JSON.stringify(this.form));
      },

      onCancel() {
        if (this.snapshot) {
          this.form = JSON.parse(JSON.stringify(this.snapshot));
        } else {
          this.form = emptyForm();
        }
        this.mode  = 'view';
        this.error = '';
        this.saved = false;
      },

      onClose() {
        this.form     = emptyForm();
        this.snapshot = null;
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      },

      onPrint() {
        if (!this.form.ref) return;
        const p = new URLSearchParams({
          ref:             this.form.ref,
          alert_name:      this.form.alert_name,
          condition_field: this.form.condition_field,
          operator:        this.form.operator,
          threshold_value: this.form.threshold_value ?? '',
          channel:         this.form.channel,
          severity:        this.form.severity,
          is_enabled:      this.form.is_enabled ? '1' : '0',
          description:     this.form.description ?? '',
          status:          this.form.status,
        });
        window.open('/modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_print.php?' + p.toString(), '_blank');
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.alert_name.trim())      { this.error = 'Alert name is required.';       return; }
        if (!this.form.condition_field.trim()) { this.error = 'Condition field is required.';   return; }
        if (this.form.threshold_value === '' || this.form.threshold_value === null) {
          this.error = 'Threshold value is required.'; return;
        }
        if (!this.form.channel)                { this.error = 'Channel is required.';           return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_data.php?action=${action}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(this.form),
          });
          const d = await r.json();
          if (!r.ok || d.error) { this.error = d.error ?? 'Save failed.'; return; }
          this.snapshot = JSON.parse(JSON.stringify(this.form));
          this.saved    = true;
          this.mode     = 'view';
        } finally {
          this.saving = false;
        }
      },
    },

    mounted() {
      document.addEventListener('stg_financial_alerts-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:             d.ref             ?? '',
          alert_name:      d.alert_name      ?? '',
          condition_field: d.condition_field ?? '',
          operator:        d.operator        ?? '>',
          threshold_value: d.threshold_value ?? '',
          channel:         d.channel         ?? 'email',
          severity:        d.severity        ?? 'medium',
          is_enabled:      !!d.is_enabled,
          description:     d.description     ?? '',
          status:          d.status          ?? 'active',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#sfa-app');
})();
