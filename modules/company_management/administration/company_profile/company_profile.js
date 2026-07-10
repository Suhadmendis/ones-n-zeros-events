// company_profile.js

const { createApp } = Vue;

createApp({
  data() {
    return {
      loading:    false,
      saving:     false,
      saveStatus: '',
      saveMsg:    '',
      logoFile: null,
      sealFile: null,
      form: {
        id: null,
        // Basic Information
        name:             '',
        short_name:       '',
        reg_number:       '',
        tin:              '',
        vat_number:       '',
        business_type:    '',
        industry:         '',
        established_date: '',
        company_status:   'Active',
        // Contact Information
        telephone: '',
        mobile:    '',
        email:     '',
        website:   '',
        fax:       '',
        // Address
        address_line1: '',
        address_line2: '',
        city:          '',
        district:      '',
        province:      '',
        postal_code:   '',
        country:       '',
        // Branding
        logo_path: '',
        seal_path: '',
        // Financial
        base_currency:        'LKR',
        financial_year_start: '',
        financial_year_end:   '',
        // Regional
        language:    'en',
        timezone:    'Asia/Colombo',
        date_format: 'DD/MM/YYYY',
        time_format: '24h',
        // Bank Information
        bank_name:      '',
        branch_name:    '',
        account_name:   '',
        account_number: '',
        swift_code:     '',
        // Report Settings
        print_logo:    true,
        print_seal:    false,
        report_footer: '',
        paper_size:    'A4',
      },
      original: {},
    };
  },

  computed: {
    isDirty() {
      return (
        JSON.stringify(this.form) !== JSON.stringify(this.original) ||
        this.logoFile !== null ||
        this.sealFile !== null
      );
    },
  },

  mounted() {
    this.fetchCompany();
  },

  methods: {
    fetchCompany() {
      this.loading = true;
      axios.get('/modules/company_management/administration/company_profile/company_profile_data.php?action=get')
        .then(res => {
          this.form     = { ...this.form, ...res.data };
          this.original = { ...this.form };
        })
        .catch(err => console.error('Failed to load company data:', err))
        .finally(() => { this.loading = false; });
    },

    onLogoChange(e) {
      this.logoFile = e.target.files[0] || null;
      if (this.logoFile) {
        this.form.logo_path = URL.createObjectURL(this.logoFile);
      }
    },

    onSealChange(e) {
      this.sealFile = e.target.files[0] || null;
      if (this.sealFile) {
        this.form.seal_path = URL.createObjectURL(this.sealFile);
      }
    },

    onSave() {
      this.saving = true;
      const payload = new FormData();
      Object.entries(this.form).forEach(([k, v]) => {
        if (v !== null && v !== undefined) payload.append(k, v);
      });
      if (this.logoFile) payload.append('logo_file', this.logoFile);
      if (this.sealFile) payload.append('seal_file', this.sealFile);

      axios.post(
        '/modules/company_management/administration/company_profile/company_profile_data.php?action=update',
        payload
      )
        .then(res => {
          this.form       = { ...this.form, ...res.data };
          this.original   = { ...this.form };
          this.logoFile   = null;
          this.sealFile   = null;
          this.saveStatus = 'ok';
          this.saveMsg    = '';
          setTimeout(() => { this.saveStatus = ''; }, 3000);
        })
        .catch(err => {
          this.saveStatus = 'error';
          this.saveMsg    = err.response?.data?.error ?? 'Save failed. Please try again.';
        })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#company-app');
