// approval_workflow.js — Approval Workflow Vue app

(function () {
  const { createApp } = Vue;

  const emptyForm = () => ({
    ref:            '',
    workflow_name:  '',
    module:         '',
    approver_name:  '',
    approver_ref:   '',
    min_amount:     '',
    max_amount:     '',
    approval_order: 1,
    is_mandatory:   true,
    description:    '',
    status:         'active',
  });

  createApp({
    data() {
      return {
        title:    'Approval Workflow',
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
          const r = await fetch('/modules/finance/compliance/approval_workflow/approval_workflow_data.php?action=next_ref');
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
          workflow_name:  this.form.workflow_name,
          module:         this.form.module,
          approver_name:  this.form.approver_name,
          approver_ref:   this.form.approver_ref   ?? '',
          min_amount:     this.form.min_amount      ?? '',
          max_amount:     this.form.max_amount      ?? '',
          approval_order: this.form.approval_order,
          is_mandatory:   this.form.is_mandatory ? '1' : '0',
          description:    this.form.description    ?? '',
          status:         this.form.status,
        });
        window.open('/modules/finance/compliance/approval_workflow/approval_workflow_print.php?' + p.toString(), '_blank');
      },

      async onSave() {
        this.error = '';
        this.saved = false;

        if (!this.form.workflow_name.trim()) { this.error = 'Workflow name is required.';  return; }
        if (!this.form.module)               { this.error = 'Module is required.';          return; }
        if (!this.form.approver_name.trim()) { this.error = 'Approver name is required.';  return; }

        if (this.mode === 'edit') {
          const chk = await fetch(`/modules/finance/compliance/approval_workflow/approval_workflow_data.php?action=exists&ref=${encodeURIComponent(this.form.ref)}`);
          const { exists } = await chk.json();
          if (!exists) { this.error = 'Record not found. Please search again.'; return; }
        }

        this.saving = true;
        try {
          const action = this.mode === 'edit' ? 'update' : 'save';
          const r = await fetch(`/modules/finance/compliance/approval_workflow/approval_workflow_data.php?action=${action}`, {
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
      document.addEventListener('approval_workflow-selected', (e) => {
        const d = e.detail;
        this.form = {
          ref:            d.ref            ?? '',
          workflow_name:  d.workflow_name  ?? '',
          module:         d.module         ?? '',
          approver_name:  d.approver_name  ?? '',
          approver_ref:   d.approver_ref   ?? '',
          min_amount:     d.min_amount     ?? '',
          max_amount:     d.max_amount     ?? '',
          approval_order: d.approval_order ?? 1,
          is_mandatory:   !!d.is_mandatory,
          description:    d.description   ?? '',
          status:         d.status        ?? 'active',
        };
        this.snapshot = JSON.parse(JSON.stringify(this.form));
        this.mode     = 'view';
        this.error    = '';
        this.saved    = false;
      });
    },
  }).mount('#apw-app');
})();
