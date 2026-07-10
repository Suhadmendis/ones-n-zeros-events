// period_closing.js — Period Closing Vue app

(function () {
  const { createApp } = Vue;

  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  const emptyForm = () => ({
    ref:            '',
    period:         '',
    period_name:    '',
    closing_date:   '',
    total_revenue:  '',
    total_expenses: '',
    notes:          '',
    status:         'open',
  });

  createApp({
    data() {
      return {
        title:    'Period Closing',
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
      netProfit() {
        return (parseFloat(this.form.total_revenue) || 0) - (parseFloat(this.form.total_expenses) || 0);
      },
      netProfitFmt() {
        return this.netProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      },
      isDirty() {
        return JSON.stringify(this.form) !== JSON.stringify(this.snapshot);
      },
    },

    methods: {
      syncPeriodName() {
        if (!this.form.period) return;
        const [y, m] = this.form.period.split('-');
        this.form.period_name = MONTHS[parseInt(m, 10) - 1] + ' ' + y;
      },

      async fetchNextRef() {
        this.loading = true;
        try {
          const r = await fetch('/modules/finance/compliance/period_closing/period_closing_data.php?action=next_ref');
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
          ref:            this.form.ref,
          period:         this.form.period,
          period_name:    this.form.period_name,
          closing_date:   this.form.closing_date,
          total_revenue:  this.form.total_revenue  ?? 0,
          total_expenses: this.form.total_expenses ?? 0,
          net_profit:     this.netProfit,
          notes:          this.form.notes          ?? '',
          status:         this.form.status,
        });
        window.open('/modules/finance/compliance/period_closing/period_closing_print.php?' + p.toString(), '_blank');
      },

      async onClosePeriod() {
        if (!this.form.ref || this.form.status !== 'open') return;
        if (!confirm('Close this period? Posting will no longer be allowed.')) return;
        this.form.status = 'closed';
        await this.onSave();
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.period)       { this.error = 'Period is required.';       return; }
        if (!this.form.period_name.trim()) { this.error = 'Period name is required.'; return; }
        if (!this.form.closing_date) { this.error = 'Closing date is required.'; return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/finance/compliance/period_closing/period_closing_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/finance/compliance/period_closing/period_closing_data.php?action=${action}`, {
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
      document.addEventListener('period_closing-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:            d.ref            ?? '',
          period:         d.period         ?? '',
          period_name:    d.period_name    ?? '',
          closing_date:   d.closing_date   ?? '',
          total_revenue:  d.total_revenue  ?? '',
          total_expenses: d.total_expenses ?? '',
          notes:          d.notes          ?? '',
          status:         d.status         ?? 'open',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#prc-app');
})();
