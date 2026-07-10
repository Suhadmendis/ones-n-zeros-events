const { createApp } = Vue;

createApp({
  data() {
    return {
      driver_id: '',
      status: 'all',
      drivers: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
    };
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/driver_loan_report/driver_loan_report_data.php?action=list_drivers')
      .then(r => { this.drivers = r.data; })
      .catch(() => {});
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      this.loading = true;
      this.error = '';
      const params = new URLSearchParams({ action: 'report', status: this.status });
      if (this.driver_id) params.set('driver_ref', this.driver_id);
      axios.get('/modules/reports/staff_reports/driver_loan_report/driver_loan_report_data.php?' + params.toString())
        .then(r => { this.rows = r.data.rows; this.ran = true; })
        .catch(() => { this.error = 'Failed to load report.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#driver-loan-app');
