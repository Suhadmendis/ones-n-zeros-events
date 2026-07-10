// edit_deactivate_user.js — Edit / deactivate user Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName:   SYSTEM_NAME,
      title:        '',
      userLoaded:   false,
      saving:       false,
      saved:        false,
      savedMessage: '',
      error:        '',
      roles:        [],
      original: {},
      form: {
        ref:           '',
        full_name:     '',
        email:         '',
        role_refs:     [],
        record_status: 'active',
      },
    };
  },

  computed: {
    isDirty() {
      if (!this.userLoaded) return false;
      return (
        this.form.full_name     !== this.original.full_name ||
        this.form.email         !== this.original.email     ||
        this.form.record_status !== this.original.record_status ||
        JSON.stringify([...this.form.role_refs].sort()) !== JSON.stringify([...(this.original.role_refs || [])].sort())
      );
    },
  },

  mounted() {
    this.fetchTitle();
    this.fetchRoles();
    document.addEventListener('edit-user-selected', (e) => this.loadUser(e.detail));
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => {});
    },

    fetchRoles() {
      axios.get('/modules/company_management/user_management/edit_deactivate_user/edit_deactivate_user_data.php?action=list_roles')
        .then(res => { this.roles = res.data; })
        .catch(() => {});
    },

    loadUser(data) {
      this.form.ref           = data.ref;
      this.form.full_name     = data.full_name;
      this.form.email         = data.email;
      this.form.role_refs     = [...(data.role_refs || [])];
      this.form.record_status = data.record_status;
      this.original           = { ...this.form, role_refs: [...this.form.role_refs] };
      this.userLoaded         = true;
      this.saved              = false;
      this.error              = '';
    },

    onCancel() { if (this.userLoaded) { this.form = { ...this.original, role_refs: [...this.original.role_refs] }; } this.saved = false; this.error = ''; },
    onClose()  { this.userLoaded = false; this.form = { ref: '', full_name: '', email: '', role_refs: [], record_status: 'active' }; this.saved = false; this.error = ''; },
    onReset()  { this.onCancel(); },

    onSave() {
      if (!this.isDirty) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      axios.post('/modules/company_management/user_management/edit_deactivate_user/edit_deactivate_user_data.php?action=update', this.form)
        .then(() => {
          this.original    = { ...this.form };
          this.saved       = true;
          this.savedMessage = 'User updated successfully.';
        })
        .catch(err => {
          this.error = err.response?.data?.error || 'Failed to update user.';
        })
        .finally(() => { this.saving = false; });
    },

    onDeactivate() {
      if (!confirm('Deactivate this user?')) return;
      this.form.record_status = 'inactive';
      this.onSave();
      this.savedMessage = 'User deactivated.';
    },

    onActivate() {
      if (!confirm('Activate this user?')) return;
      this.form.record_status = 'active';
      this.onSave();
      this.savedMessage = 'User activated.';
    },
  },
}).mount('#edit-user-app');
