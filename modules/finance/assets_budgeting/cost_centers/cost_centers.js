// cost_centers.js — Cost Centers Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:               '',
    cost_center_code:  '',
    cost_center_name:  '',
    department:        '',
    manager:           '',
    budget_amount:     '',
    actual_amount:     '',
    description:       '',
    status:            'active',
  });

  createApp({
    data() {
      return {
        title:    'Cost Centers',
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
      variance() {
        return (parseFloat(this.form.budget_amount) || 0) - (parseFloat(this.form.actual_amount) || 0);
      },
      varianceFmt() {
        return this.variance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      },
      isDirty() {
        return JSON.stringify(this.form) !== JSON.stringify(this.snapshot);
      },
    },

    methods: {
      async fetchNextRef() {
        this.loading = true;
        try {
          const r = await fetch('/modules/finance/assets_budgeting/cost_centers/cost_centers_data.php?action=next_ref');
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
          cost_center_code: this.form.cost_center_code,
          cost_center_name: this.form.cost_center_name,
          department:       this.form.department     ?? '',
          manager:          this.form.manager        ?? '',
          budget_amount:    this.form.budget_amount  ?? 0,
          actual_amount:    this.form.actual_amount  ?? 0,
          variance:         this.variance,
          description:      this.form.description   ?? '',
          status:           this.form.status,
        });
        window.open('/modules/finance/assets_budgeting/cost_centers/cost_centers_print.php?' + p.toString(), '_blank');
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.cost_center_code.trim()) { this.error = 'Cost center code is required.'; return; }
        if (!this.form.cost_center_name.trim()) { this.error = 'Cost center name is required.'; return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/finance/assets_budgeting/cost_centers/cost_centers_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/finance/assets_budgeting/cost_centers/cost_centers_data.php?action=${action}`, {
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
      document.addEventListener('cost_centers-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:              d.ref               ?? '',
          cost_center_code: d.cost_center_code  ?? '',
          cost_center_name: d.cost_center_name  ?? '',
          department:       d.department        ?? '',
          manager:          d.manager           ?? '',
          budget_amount:    d.budget_amount     ?? '',
          actual_amount:    d.actual_amount     ?? '',
          description:      d.description      ?? '',
          status:           d.status           ?? 'active',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#cst-app');
})();
