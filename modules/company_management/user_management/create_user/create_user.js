// create_user.js — User module Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading: false,
      saving:  false,
      saved:   false,
      error:   '',
      roles:   [],
      form: {
        ref:           '',
        full_name:     '',
        email:         '',
        password:      '',
        role_refs:     [],
        record_status: 'active',
      },
    };
  },

  computed: {
    isDirty() {
      return (
        this.form.full_name.trim() !== '' ||
        this.form.email.trim()     !== '' ||
        this.form.password.trim()  !== '' ||
        this.form.role_refs.length  > 0   ||
        this.form.record_status    !== 'active'
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    this.fetchRoles();
    document.addEventListener('user-selected', (e) => this.loadUser(e.detail));
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    fetchRoles() {
      axios.get('/modules/company_management/user_management/create_user/create_user_data.php?action=list_roles')
        .then(res => { this.roles = res.data; })
        .catch(() => {});
    },

    loadUser(data) {
      this.form.ref           = data.ref;
      this.form.full_name     = data.full_name;
      this.form.email         = data.email;
      this.form.password      = ''; // never populate password from search
      this.form.role_refs     = data.role_refs || [];
      this.form.record_status = data.record_status;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.full_name     = '';
      this.form.email         = '';
      this.form.password      = '';
      this.form.role_refs     = [];
      this.form.record_status = 'active';
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      axios.post('/modules/company_management/user_management/create_user/create_user_data.php?action=save', this.form)
        .then(() => {
          this.saved = true;
          this.onReset();
          this.fetchRefNumber();
        })
        .catch(err => {
          this.error = err.response?.data?.error || 'Failed to save user.';
          console.error(err);
        })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#user-app');
