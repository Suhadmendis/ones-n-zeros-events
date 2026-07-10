// cleaner_master_file.js — Cleaner module Vue app

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
        ref:           '',
        name:          '',
        phone:         '',
        status:        'active',
        date_of_birth: '',
        joining_date:  '',
        employee_ref:  '',
        employee_name: '',
      },
    };
  },

  computed: {
    isDirty() {
      return (
        this.form.name.trim()          !== '' ||
        this.form.phone.trim()         !== '' ||
        this.form.date_of_birth        !== '' ||
        this.form.joining_date         !== '' ||
        this.form.status               !== 'active' ||
        this.form.employee_ref         !== ''
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('cleaner-selected', (e) => this.loadCleaner(e.detail));
    document.addEventListener('cleaner-employee-selected', (e) => {
      this.form.employee_ref = e.detail.ref;
      this.form.employee_name = e.detail.full_name;
    });
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    loadCleaner(data) {
      this.form.ref           = data.ref;
      this.form.name          = data.name;
      this.form.phone         = data.phone;
      this.form.status        = data.status;
      this.form.date_of_birth = data.date_of_birth ?? '';
      this.form.joining_date  = data.joining_date  ?? '';
      this.form.employee_ref  = data.employee_ref  ?? '';
      this.form.employee_name = data.m_employees?.full_name ?? '';
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    clearEmployee() { this.form.employee_ref = ''; this.form.employee_name = ''; },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:           this.form.ref,
            name:          this.form.name,
            phone:         this.form.phone,
            status:        this.form.status,
            date_of_birth: this.form.date_of_birth,
            joining_date:  this.form.joining_date,
            employee_name: this.form.employee_name,
          });
          window.open('/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.name          = '';
      this.form.phone         = '';
      this.form.status        = 'active';
      this.form.date_of_birth = '';
      this.form.joining_date  = '';
      this.form.employee_ref  = '';
      this.form.employee_name = '';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save cleaner.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#cleaner-app');
