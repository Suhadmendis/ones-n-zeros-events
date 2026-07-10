const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      vehicleId: '',
      driverId:  '',
      vehicles: [],
      drivers:  [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    this.loadMeta();
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    loadMeta() {
      axios.get('/modules/reports/operations_reports/trip_revenue/trip_revenue_data.php', { params: { action: 'list_vehicles' } })
        .then(r => { this.vehicles = r.data; });
      axios.get('/modules/reports/operations_reports/trip_revenue/trip_revenue_data.php', { params: { action: 'list_drivers' } })
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
      if (this.vehicleId) params.vehicle_ref = this.vehicleId;
      if (this.driverId)  params.driver_ref  = this.driverId;
      axios.get('/modules/reports/operations_reports/trip_revenue/trip_revenue_data.php', { params })
        .then(r => { this.rows = r.data.rows || []; this.summary = r.data.summary || null; this.ran = true; })
        .catch(() => { this.error = 'Failed to load data.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#trip-revenue-app');
