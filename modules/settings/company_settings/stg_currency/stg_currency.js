// stg_currency.js — Currency (Company Settings) Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:              '',
    currency_code:    '',
    currency_name:    '',
    symbol:           '',
    exchange_rate:    '',
    is_base_currency: false,
    effective_date:   '',
    description:      '',
    status:           'active',
  });

  createApp({
    data() {
      return {
        title:    'Currency',
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
          const r = await fetch('/modules/settings/company_settings/stg_currency/stg_currency_data.php?action=next_ref');
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
          ref:              this.form.ref,
          currency_code:    this.form.currency_code,
          currency_name:    this.form.currency_name,
          symbol:           this.form.symbol           ?? '',
          exchange_rate:    this.form.exchange_rate     ?? 1,
          is_base_currency: this.form.is_base_currency ? '1' : '0',
          effective_date:   this.form.effective_date,
          description:      this.form.description      ?? '',
          status:           this.form.status,
        });
        window.open('/modules/settings/company_settings/stg_currency/stg_currency_print.php?' + p.toString(), '_blank');
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.currency_code.trim()) { this.error = 'Currency code is required.';  return; }
        if (!this.form.currency_name.trim()) { this.error = 'Currency name is required.';  return; }
        if (!this.form.effective_date)       { this.error = 'Effective date is required.'; return; }
        const rate = parseFloat(this.form.exchange_rate);
        if (isNaN(rate) || rate <= 0) { this.error = 'Exchange rate must be greater than zero.'; return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/settings/company_settings/stg_currency/stg_currency_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/settings/company_settings/stg_currency/stg_currency_data.php?action=${action}`, {
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
      document.addEventListener('stg_currency-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:              d.ref              ?? '',
          currency_code:    d.currency_code    ?? '',
          currency_name:    d.currency_name    ?? '',
          symbol:           d.symbol           ?? '',
          exchange_rate:    d.exchange_rate    ?? '',
          is_base_currency: !!d.is_base_currency,
          effective_date:   d.effective_date   ?? '',
          description:      d.description      ?? '',
          status:           d.status           ?? 'active',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#stgcur-app');
})();
