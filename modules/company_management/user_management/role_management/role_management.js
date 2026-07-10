// role_management.js — Role CRUD Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading:    false,
      saving:     false,
      saved:      false,
      error:      '',
      roles:      [],
      editingRef: '', // '' means creating a new role
      form: {
        name:          '',
        description:   '',
        record_status: 'active',
      },
    };
  },

  computed: {
    isDirty() {
      return this.form.name.trim() !== '' || this.form.description.trim() !== '';
    },
  },

  mounted() {
    this.fetchTitle();
    this.fetchRoles();
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => { this.title = 'Role Management'; });
    },

    fetchRoles() {
      this.loading = true;
      axios.get('/modules/company_management/user_management/role_management/role_management_data.php?action=list')
        .then(res => { this.roles = res.data; })
        .catch(err => { this.error = 'Failed to load roles.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    onNew() { this.onReset(); },

    onEditRole(role) {
      this.editingRef = role.ref;
      this.form = { name: role.name, description: role.description || '', record_status: role.record_status };
      this.saved = false;
      this.error = '';
    },

    onReset() {
      this.editingRef = '';
      this.form = { name: '', description: '', record_status: 'active' };
      this.saved = false;
      this.error = '';
    },

    onToggleStatus(role) {
      const next = role.record_status === 'active' ? 'inactive' : 'active';
      if (!confirm((next === 'inactive' ? 'Deactivate' : 'Activate') + ' role "' + role.name + '"?')) return;
      axios.post('/modules/company_management/user_management/role_management/role_management_data.php?action=update', {
        ref: role.ref, record_status: next,
      })
        .then(() => { this.fetchRoles(); })
        .catch(err => { this.error = err.response?.data?.error || 'Failed to update role.'; });
    },

    onSave() {
      if (!this.isDirty) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const req = this.editingRef
        ? axios.post('/modules/company_management/user_management/role_management/role_management_data.php?action=update', { ref: this.editingRef, ...this.form })
        : axios.post('/modules/company_management/user_management/role_management/role_management_data.php?action=save', this.form);

      req.then(() => {
          this.saved = true;
          this.onReset();
          this.fetchRoles();
        })
        .catch(err => {
          this.error = err.response?.data?.error || 'Failed to save role.';
        })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#role-mgmt-app');
