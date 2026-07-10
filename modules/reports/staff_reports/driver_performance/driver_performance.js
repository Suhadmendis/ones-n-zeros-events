const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      driverId: '',
      drivers: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      totals: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    this.loadDrivers();
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    loadDrivers() {
      axios.get('/modules/reports/staff_reports/driver_performance/driver_performance_data.php', { params: { action: 'list_drivers' } })
        .then(r => { this.drivers = r.data; });
    },
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.from = from;
      this.to   = to;
      this.load();
    },
    load() {
      if (!this.from || !this.to) { this.error = 'Please select both dates.'; return; }
      this.loading = true;
      this.error = '';
      const params = { action: 'report', from: this.from, to: this.to };
      if (this.driverId) params.driver_ref = this.driverId;
      axios.get('/modules/reports/staff_reports/driver_performance/driver_performance_data.php', { params })
        .then(r => { this.rows = r.data.rows || []; this.totals = r.data.totals || null; this.ran = true; })
        .catch(() => { this.error = 'Failed to load data.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#driver-perf-app');
