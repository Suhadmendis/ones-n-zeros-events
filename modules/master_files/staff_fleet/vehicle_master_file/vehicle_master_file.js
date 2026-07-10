// vehicle_master_file.js — Vehicle module Vue app

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
        plate_number:  '',
        make:          '',
        model:         '',
        type:          '',
        fuel_type:     '',
        status:        'active',
        last_location: 'Depot',
        mileage:       0,
        year:          '',
        capacity:      0,
      },
    };
  },

  computed: {
    isDirty() {
      return (
        this.form.plate_number.trim()  !== '' ||
        this.form.make.trim()          !== '' ||
        this.form.model.trim()         !== '' ||
        this.form.type                 !== '' ||
        this.form.fuel_type            !== '' ||
        this.form.year                 !== '' ||
        this.form.mileage              !== 0  ||
        this.form.capacity             !== 0  ||
        this.form.last_location.trim() !== 'Depot' ||
        this.form.status               !== 'active'
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('vehicle-selected', (e) => this.loadVehicle(e.detail));
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    loadVehicle(data) {
      this.form.ref           = data.ref;
      this.form.plate_number  = data.plate_number;
      this.form.make          = data.make;
      this.form.model         = data.model;
      this.form.type          = data.type;
      this.form.fuel_type     = data.fuel_type;
      this.form.status        = data.status;
      this.form.last_location = data.last_location ?? 'Depot';
      this.form.mileage       = data.mileage       ?? 0;
      this.form.year          = data.year          ?? '';
      this.form.capacity      = data.capacity      ?? 0;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:           this.form.ref,
            plate_number:  this.form.plate_number,
            make:          this.form.make,
            model:         this.form.model,
            type:          this.form.type,
            fuel_type:     this.form.fuel_type,
            status:        this.form.status,
            last_location: this.form.last_location,
            mileage:       this.form.mileage,
            year:          this.form.year,
            capacity:      this.form.capacity,
          });
          window.open('/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.plate_number  = '';
      this.form.make          = '';
      this.form.model         = '';
      this.form.type          = '';
      this.form.fuel_type     = '';
      this.form.status        = 'active';
      this.form.last_location = 'Depot';
      this.form.mileage       = 0;
      this.form.year          = '';
      this.form.capacity      = 0;
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
        axios.post('/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save vehicle.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#vehicle-app');
