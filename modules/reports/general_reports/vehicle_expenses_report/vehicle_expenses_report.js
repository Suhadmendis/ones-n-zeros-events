const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      vehicleId: '',
      vehicles: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    this.loadVehicles().then(() => this.load());
  },
  methods: {
    fmt: ReportUtils.fmt,
    categoryBadge(cat) {
      const map = {
        repair:    'bg-danger',
        service:   'bg-primary',
        tyre:      'bg-warning text-dark',
        battery:   'bg-info text-dark',
        insurance: 'bg-success',
        other:     'bg-secondary',
      };
      return map[(cat || '').toLowerCase()] || 'bg-secondary';
    },
    loadVehicles() {
      return axios
        .get('/modules/reports/general_reports/vehicle_expenses_report/vehicle_expenses_report_data.php', {
          params: { action: 'list_vehicles' },
        })
        .then(r => { this.vehicles = r.data || []; })
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
      this.error = '';
      const params = { action: 'report', from: this.from, to: this.to };
      if (this.vehicleId) params.vehicle_ref = this.vehicleId;
      axios
        .get('/modules/reports/general_reports/vehicle_expenses_report/vehicle_expenses_report_data.php', { params })
        .then(r => {
          this.rows    = r.data.rows    || [];
          this.summary = r.data.summary || null;
          this.ran     = true;
        })
        .catch(() => { this.error = 'Failed to load report.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#veh-expenses-app');
