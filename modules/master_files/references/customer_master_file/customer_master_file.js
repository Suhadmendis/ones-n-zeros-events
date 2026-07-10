// customer_master_file.js — Customer module Vue app

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
        ref:                 '',
        code:                '',
        customer_name:       '',
        customer_type_ref:   '',
        customer_type_name:  '',
        contact_person:      '',
        phone:               '',
        mobile:              '',
        email:               '',
        address:             '',
        city:                '',
        country:             '',
        tax_number:          '',
        credit_limit:        0,
        payment_terms_days:  0,
        remarks:             '',
        record_status:       'active',
      },
    };
  },

  computed: {
    isDirty() {
      return (
        this.form.code.trim()               !== '' ||
        this.form.customer_name.trim()      !== '' ||
        this.form.customer_type_ref         !== '' ||
        this.form.contact_person.trim()     !== '' ||
        this.form.phone.trim()              !== '' ||
        this.form.mobile.trim()             !== '' ||
        this.form.email.trim()              !== '' ||
        this.form.address.trim()            !== '' ||
        this.form.city.trim()               !== '' ||
        this.form.country.trim()            !== '' ||
        this.form.tax_number.trim()         !== '' ||
        this.form.credit_limit              !== 0 ||
        this.form.payment_terms_days        !== 0 ||
        this.form.remarks.trim()            !== '' ||
        this.form.record_status             !== 'active'
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('customer-selected', (e) => this.loadCustomer(e.detail));
    document.addEventListener('cu-customer-type-selected', (e) => {
      this.form.customer_type_ref  = e.detail.ref;
      this.form.customer_type_name = e.detail.name;
    });
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(() => { this.error = 'Failed to load reference number.'; })
        .finally(() => { this.loading = false; });
    },

    clearCustomerType() { this.form.customer_type_ref = ''; this.form.customer_type_name = ''; },

    loadCustomer(data) {
      this.form.ref                = data.ref;
      this.form.code                = data.code ?? '';
      this.form.customer_name       = data.customer_name;
      this.form.customer_type_ref   = data.customer_type_ref ?? '';
      this.form.customer_type_name  = data.m_customer_types?.name ?? '';
      this.form.contact_person      = data.contact_person ?? '';
      this.form.phone               = data.phone   ?? '';
      this.form.mobile              = data.mobile  ?? '';
      this.form.email               = data.email   ?? '';
      this.form.address             = data.address ?? '';
      this.form.city                = data.city ?? '';
      this.form.country             = data.country ?? '';
      this.form.tax_number          = data.tax_number ?? '';
      this.form.credit_limit        = data.credit_limit ?? 0;
      this.form.payment_terms_days  = data.payment_terms_days ?? 0;
      this.form.remarks             = data.remarks ?? '';
      this.form.record_status       = data.record_status;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/master_files/references/customer_master_file/customer_master_file_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:                 this.form.ref,
            code:                this.form.code,
            customer_name:       this.form.customer_name,
            customer_type_name:  this.form.customer_type_name,
            contact_person:      this.form.contact_person,
            phone:               this.form.phone,
            mobile:              this.form.mobile,
            email:               this.form.email,
            address:             this.form.address,
            city:                this.form.city,
            country:             this.form.country,
            tax_number:          this.form.tax_number,
            credit_limit:        String(this.form.credit_limit ?? ''),
            payment_terms_days:  String(this.form.payment_terms_days ?? ''),
            remarks:             this.form.remarks,
            record_status:       this.form.record_status,
          });
          window.open('/modules/master_files/references/customer_master_file/customer_master_file_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.code               = '';
      this.form.customer_name      = '';
      this.form.customer_type_ref  = '';
      this.form.customer_type_name = '';
      this.form.contact_person     = '';
      this.form.phone              = '';
      this.form.mobile             = '';
      this.form.email              = '';
      this.form.address            = '';
      this.form.city               = '';
      this.form.country            = '';
      this.form.tax_number         = '';
      this.form.credit_limit       = 0;
      this.form.payment_terms_days = 0;
      this.form.remarks            = '';
      this.form.record_status      = 'active';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.customer_name.trim() === '') { this.error = 'Customer name is required.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/master_files/references/customer_master_file/customer_master_file_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save customer.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/master_files/references/customer_master_file/customer_master_file_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#customer-app');
