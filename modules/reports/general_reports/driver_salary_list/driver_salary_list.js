const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      driver_id: '',
      drivers:   [],
      loading:   false,
      ran:       false,
      error:     '',
      rows:      [],
      summary:   { trip_count: 0, total_driver_salary: 0, total_earning: 0 },
      months:    ReportUtils.buildMonths(),
    };
  },
  mounted() {
    this.loadDrivers();
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    loadDrivers() {
      axios
        .get('/modules/reports/general_reports/driver_salary_list/driver_salary_list_data.php', { params: { action: 'list_drivers' } })
        .then(r => { this.drivers = r.data; })
        .catch(() => {});
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
      this.error   = '';
      const params = { action: 'report', from: this.from, to: this.to };
      if (this.driver_id) params.driver_ref = this.driver_id;
      axios
        .get('/modules/reports/general_reports/driver_salary_list/driver_salary_list_data.php', { params })
        .then(r => {
          this.rows    = r.data.rows    || [];
          this.summary = r.data.summary || { trip_count: 0, total_driver_salary: 0, total_earning: 0 };
          this.ran     = true;
        })
        .catch(() => { this.error = 'Failed to load data.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#driver-salary-list-app');
