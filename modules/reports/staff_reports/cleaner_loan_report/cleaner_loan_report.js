const { createApp } = Vue;

createApp({
  data() {
    return {
      cleaner_id: '',
      status: 'all',
      cleaners: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
    };
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/cleaner_loan_report/cleaner_loan_report_data.php?action=list_cleaners')
      .then(r => { this.cleaners = r.data; })
      .catch(() => {});
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      this.loading = true;
      this.error = '';
      const params = new URLSearchParams({ action: 'report', status: this.status });
      if (this.cleaner_id) params.set('cleaner_ref', this.cleaner_id);
      axios.get('/modules/reports/staff_reports/cleaner_loan_report/cleaner_loan_report_data.php?' + params.toString())
        .then(r => { this.rows = r.data.rows; this.ran = true; })
        .catch(() => { this.error = 'Failed to load report.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#cleaner-loan-app');
