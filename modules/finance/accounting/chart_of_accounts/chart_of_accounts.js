// chart_of_accounts.js — Chart of Accounts Vue app

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
      isExisting: false,
      form: {
        ref:              '',
        account_code:     '',
        account_name:     '',
        account_type:     '',
        account_sub_type: '',
        parent_code:      '',
        description:      '',
        status:           'active',
      },
    };
  },

  computed: {
    isDirty() {
      return (
        this.form.account_code.trim()  !== '' ||
        this.form.account_name.trim()  !== '' ||
        this.form.account_type         !== ''
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('chart_of_accounts-selected', (e) => this.loadAccount(e.detail));
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(() => { this.error = 'Failed to load reference number.'; })
        .finally(() => { this.loading = false; });
    },

    loadAccount(data) {
      this.form.ref              = data.ref;
      this.form.account_code     = data.account_code;
      this.form.account_name     = data.account_name;
      this.form.account_type     = data.account_type;
      this.form.account_sub_type = data.account_sub_type ?? '';
      this.form.parent_code      = data.parent_code      ?? '';
      this.form.description      = data.description      ?? '';
      this.form.status           = data.status;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:              this.form.ref,
            account_code:     this.form.account_code,
            account_name:     this.form.account_name,
            account_type:     this.form.account_type,
            account_sub_type: this.form.account_sub_type,
            parent_code:      this.form.parent_code,
            description:      this.form.description,
            status:           this.form.status,
          });
          window.open('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.account_code     = '';
      this.form.account_name     = '';
      this.form.account_type     = '';
      this.form.account_sub_type = '';
      this.form.parent_code      = '';
      this.form.description      = '';
      this.form.status           = 'active';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.account_code.trim() === '')  { this.error = 'Account code is required.'; return; }
      if (this.form.account_name.trim() === '')  { this.error = 'Account name is required.'; return; }
      if (this.form.account_type === '')         { this.error = 'Account type is required.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save account.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },
  },
}).mount('#coa-app');
