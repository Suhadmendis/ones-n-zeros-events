// tax_management.js — Tax Management Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:            '',
    tax_name:       '',
    tax_code:       '',
    tax_type:       '',
    rate:           '',
    applicable_on:  'Both',
    effective_date: '',
    expiry_date:    '',
    description:    '',
    status:         'active',
  });

  createApp({
    data() {
      return {
        title:    'Tax Management',
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
          const r = await fetch('/modules/finance/compliance/tax_management/tax_management_data.php?action=next_ref');
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
          ref:            this.form.ref,
          tax_name:       this.form.tax_name,
          tax_code:       this.form.tax_code,
          tax_type:       this.form.tax_type,
          rate:           this.form.rate,
          applicable_on:  this.form.applicable_on,
          effective_date: this.form.effective_date,
          expiry_date:    this.form.expiry_date   ?? '',
          description:    this.form.description   ?? '',
          status:         this.form.status,
        });
        window.open('/modules/finance/compliance/tax_management/tax_management_print.php?' + p.toString(), '_blank');
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.tax_name.trim())       { this.error = 'Tax name is required.';       return; }
        if (!this.form.tax_code.trim())       { this.error = 'Tax code is required.';       return; }
        if (!this.form.tax_type)              { this.error = 'Tax type is required.';        return; }
        if (this.form.rate === '' || isNaN(parseFloat(this.form.rate))) {
          this.error = 'Rate is required.'; return;
        }
        if (!this.form.effective_date)        { this.error = 'Effective date is required.'; return; }
        if (this.form.expiry_date && this.form.expiry_date <= this.form.effective_date) {
          this.error = 'Expiry date must be after effective date.'; return;
        }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/finance/compliance/tax_management/tax_management_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/finance/compliance/tax_management/tax_management_data.php?action=${action}`, {
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
      document.addEventListener('tax_management-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:            d.ref            ?? '',
          tax_name:       d.tax_name       ?? '',
          tax_code:       d.tax_code       ?? '',
          tax_type:       d.tax_type       ?? '',
          rate:           d.rate           ?? '',
          applicable_on:  d.applicable_on  ?? 'Both',
          effective_date: d.effective_date ?? '',
          expiry_date:    d.expiry_date    ?? '',
          description:    d.description   ?? '',
          status:         d.status        ?? 'active',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#tax-app');
})();
