// bank_reconciliation.js — Bank Reconciliation Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:                    '',
    account_name:           '',
    account_number:         '',
    reconciliation_date:    '',
    period:                 '',
    bank_balance:           '',
    book_balance:           '',
    outstanding_deposits:   '',
    outstanding_cheques:    '',
    notes:                  '',
    status:                 'draft',
  });

  const fmt = (v) => parseFloat(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  createApp({
    data() {
      return {
        title:    'Bank Reconciliation',
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
      adjustedBank() {
        return (parseFloat(this.form.bank_balance) || 0)
             + (parseFloat(this.form.outstanding_deposits) || 0)
             - (parseFloat(this.form.outstanding_cheques) || 0);
      },
      difference() {
        return this.adjustedBank - (parseFloat(this.form.book_balance) || 0);
      },
      adjustedBankFmt() { return fmt(this.adjustedBank); },
      differenceFmt()   { return fmt(this.difference); },
      isDirty() {
        return JSON.stringify(this.form) !== JSON.stringify(this.snapshot);
      },
    },

    methods: {
      async fetchNextRef() {
        this.loading = true;
        try {
          const r = await fetch('/modules/finance/receivables_payables/bank_reconciliation/bank_reconciliation_data.php?action=next_ref');
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
        if (!this.form.ref || this.form.status === 'locked') return;
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
          ref:                  this.form.ref,
          account_name:         this.form.account_name,
          account_number:       this.form.account_number      ?? '',
          reconciliation_date:  this.form.reconciliation_date,
          period:               this.form.period,
          bank_balance:         this.form.bank_balance        ?? 0,
          book_balance:         this.form.book_balance        ?? 0,
          outstanding_deposits: this.form.outstanding_deposits ?? 0,
          outstanding_cheques:  this.form.outstanding_cheques  ?? 0,
          adjusted_bank:        this.adjustedBank,
          difference:           this.difference,
          notes:                this.form.notes               ?? '',
          status:               this.form.status,
        });
        window.open('/modules/finance/receivables_payables/bank_reconciliation/bank_reconciliation_print.php?' + p.toString(), '_blank');
      },

      async onReconcile() {
        if (!this.form.ref || Math.abs(this.difference) > 0.01) return;
        if (!confirm('Mark this reconciliation as reconciled?')) return;
        this.form.status = 'reconciled';
        this.mode = 'edit';
        await this.onSave();
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.account_name.trim())        { this.error = 'Account name is required.';         return; }
        if (!this.form.period)                     { this.error = 'Period is required.';                return; }
        if (!this.form.reconciliation_date)        { this.error = 'Reconciliation date is required.';  return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/finance/receivables_payables/bank_reconciliation/bank_reconciliation_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/finance/receivables_payables/bank_reconciliation/bank_reconciliation_data.php?action=${action}`, {
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
      document.addEventListener('bank_reconciliation-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:                    d.ref                    ?? '',
          account_name:           d.account_name           ?? '',
          account_number:         d.account_number         ?? '',
          reconciliation_date:    d.reconciliation_date    ?? '',
          period:                 d.period                 ?? '',
          bank_balance:           d.bank_balance           ?? '',
          book_balance:           d.book_balance           ?? '',
          outstanding_deposits:   d.outstanding_deposits   ?? '',
          outstanding_cheques:    d.outstanding_cheques    ?? '',
          notes:                  d.notes                  ?? '',
          status:                 d.status                 ?? 'draft',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#bnk-app');
})();
