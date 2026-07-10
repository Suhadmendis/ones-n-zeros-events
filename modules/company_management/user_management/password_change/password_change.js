// password_change.js — Password change Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      userLoaded: false,
      saving:     false,
      saved:      false,
      error:      '',
      user: { ref: '', full_name: '', email: '', id: null },
      form: { new_password: '', confirm_password: '' },
    };
  },

  computed: {
    passwordMismatch() {
      return this.form.confirm_password !== '' && this.form.new_password !== this.form.confirm_password;
    },
    isDirty() {
      return this.form.new_password.trim() !== '' || this.form.confirm_password.trim() !== '';
    },
  },

  mounted() {
    this.fetchTitle();
    document.addEventListener('password-change-user-selected', (e) => this.loadUser(e.detail));
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => {});
    },

    loadUser(data) {
      this.user.ref       = data.ref;
      this.user.full_name = data.full_name;
      this.user.email     = data.email;
      this.user.id        = data.id;
      this.userLoaded     = true;
      this.onReset();
    },

    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); this.userLoaded = false; this.user = { ref: '', full_name: '', email: '', id: null }; },

    onReset() {
      this.form.new_password     = '';
      this.form.confirm_password = '';
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty || !this.userLoaded) return;
      if (this.form.new_password.trim() === '') { this.error = 'New password is required.'; return; }
      if (this.passwordMismatch) { this.error = 'Passwords do not match.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      axios.post('/modules/company_management/user_management/password_change/password_change_data.php?action=update', {
        ref:          this.user.ref,
        new_password: this.form.new_password,
      })
        .then(() => {
          this.saved = true;
          this.onReset();
        })
        .catch(err => {
          this.error = err.response?.data?.error || 'Failed to update password.';
        })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#password-change-app');
