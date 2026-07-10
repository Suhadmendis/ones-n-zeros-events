// fixed_assets.js — Fixed Assets Vue app

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
        ref:                      '',
        asset_name:               '',
        asset_category:           '',
        purchase_date:            '',
        purchase_cost:            '',
        salvage_value:            '',
        useful_life_years:        1,
        depreciation_method:      'Straight Line',
        accumulated_depreciation: '',
        location:                 '',
        description:              '',
        status:                   'active',
      },
    };
  },

  computed: {
    netBookValue() {
      return (parseFloat(this.form.purchase_cost) || 0) - (parseFloat(this.form.accumulated_depreciation) || 0);
    },
    isDirty() {
      return this.form.asset_name.trim() !== '' || this.form.purchase_date !== '';
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('fixed_assets-selected', (e) => this.loadRecord(e.detail));
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(() => { this.error = 'Failed to load reference number.'; })
        .finally(() => { this.loading = false; });
    },

    fmtNum(v) {
      return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    loadRecord(data) {
      this.form.ref                      = data.ref;
      this.form.asset_name               = data.asset_name;
      this.form.asset_category           = data.asset_category;
      this.form.purchase_date            = data.purchase_date;
      this.form.purchase_cost            = data.purchase_cost;
      this.form.salvage_value            = data.salvage_value;
      this.form.useful_life_years        = data.useful_life_years;
      this.form.depreciation_method      = data.depreciation_method;
      this.form.accumulated_depreciation = data.accumulated_depreciation;
      this.form.location                 = data.location    ?? '';
      this.form.description              = data.description ?? '';
      this.form.status                   = data.status;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/finance/assets_budgeting/fixed_assets/fixed_assets_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) { this.error = 'No saved record found. Please search and select a record to print.'; return; }
          const params = new URLSearchParams({
            ref:                      this.form.ref,
            asset_name:               this.form.asset_name,
            asset_category:           this.form.asset_category,
            purchase_date:            this.form.purchase_date,
            purchase_cost:            this.form.purchase_cost,
            salvage_value:            this.form.salvage_value,
            useful_life_years:        this.form.useful_life_years,
            depreciation_method:      this.form.depreciation_method,
            accumulated_depreciation: this.form.accumulated_depreciation,
            net_book_value:           this.netBookValue,
            location:                 this.form.location,
            description:              this.form.description,
            status:                   this.form.status,
          });
          window.open('/modules/finance/assets_budgeting/fixed_assets/fixed_assets_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.asset_name               = '';
      this.form.asset_category           = '';
      this.form.purchase_date            = '';
      this.form.purchase_cost            = '';
      this.form.salvage_value            = '';
      this.form.useful_life_years        = 1;
      this.form.depreciation_method      = 'Straight Line';
      this.form.accumulated_depreciation = '';
      this.form.location                 = '';
      this.form.description              = '';
      this.form.status                   = 'active';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.asset_name.trim() === '')             { this.error = 'Asset name is required.'; return; }
      if (this.form.asset_category.trim() === '')         { this.error = 'Asset category is required.'; return; }
      if (this.form.purchase_date === '')                 { this.error = 'Purchase date is required.'; return; }
      if ((parseFloat(this.form.purchase_cost) || 0) <= 0) { this.error = 'Purchase cost must be greater than zero.'; return; }
      if ((parseInt(this.form.useful_life_years) || 0) < 1) { this.error = 'Useful life must be at least 1 year.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/finance/assets_budgeting/fixed_assets/fixed_assets_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save asset.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/finance/assets_budgeting/fixed_assets/fixed_assets_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#fa-app');
