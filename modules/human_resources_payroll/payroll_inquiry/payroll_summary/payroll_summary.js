const { createApp } = Vue;

createApp({
  data() {
    return {
      payroll_period_ref: '',
      periods: [],
      loading: false,
      ran: false,
      error: '',
      summary: null,
    };
  },
  mounted() {
    axios.get('/modules/human_resources_payroll/payroll_inquiry/payroll_summary/payroll_summary_data.php?action=list_periods')
      .then(r => { this.periods = r.data; })
      .catch(() => {});
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      if (!this.payroll_period_ref) return;
      this.loading = true;
      this.error = '';
      this.summary = null;
      const params = new URLSearchParams({ payroll_period_ref: this.payroll_period_ref });
      axios.get('/modules/human_resources_payroll/payroll_inquiry/payroll_summary/payroll_summary_data.php?' + params.toString())
        .then(r => {
          if (r.data.error) { this.error = r.data.error; }
          else { this.summary = r.data; }
          this.ran = true;
        })
        .catch(() => { this.error = 'Failed to load payroll summary.'; this.ran = true; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#payroll-summary-app');
