// stg_branches.js — Branches (Company Settings) Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:            '',
    branch_name:    '',
    branch_code:    '',
    address:        '',
    city:           '',
    phone:          '',
    email:          '',
    manager_name:   '',
    is_head_office: false,
    status:         'active',
  });

  createApp({
    data() {
      return {
        title:    'Branches',
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
          const r = await fetch('/modules/settings/company_settings/stg_branches/stg_branches_data.php?action=next_ref');
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
          branch_name:    this.form.branch_name,
          branch_code:    this.form.branch_code,
          address:        this.form.address        ?? '',
          city:           this.form.city           ?? '',
          phone:          this.form.phone          ?? '',
          email:          this.form.email          ?? '',
          manager_name:   this.form.manager_name   ?? '',
          is_head_office: this.form.is_head_office ? '1' : '0',
          status:         this.form.status,
        });
        window.open('/modules/settings/company_settings/stg_branches/stg_branches_print.php?' + p.toString(), '_blank');
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.branch_name.trim()) { this.error = 'Branch name is required.'; return; }
        if (!this.form.branch_code.trim()) { this.error = 'Branch code is required.'; return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/settings/company_settings/stg_branches/stg_branches_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/settings/company_settings/stg_branches/stg_branches_data.php?action=${action}`, {
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
      document.addEventListener('stg_branches-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:            d.ref            ?? '',
          branch_name:    d.branch_name    ?? '',
          branch_code:    d.branch_code    ?? '',
          address:        d.address        ?? '',
          city:           d.city           ?? '',
          phone:          d.phone          ?? '',
          email:          d.email          ?? '',
          manager_name:   d.manager_name   ?? '',
          is_head_office: !!d.is_head_office,
          status:         d.status         ?? 'active',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#br-app');
})();
