const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      loading: false,
      ran: false,
      error: '',
      rows: [],
      months: ReportUtils.buildMonths(),
    };
  },
  computed: {
    totals() {
      return {
        fuel_cost:        this.rows.reduce((s, r) => s + parseFloat(r.fuel_cost || 0), 0),
        maintenance_cost: this.rows.reduce((s, r) => s + parseFloat(r.maintenance_cost || 0), 0),
        total_cost:       this.rows.reduce((s, r) => s + parseFloat(r.total_cost || 0), 0),
      };
    },
  },
  mounted() { this.load(); },
  methods: {
    fmt: ReportUtils.fmt,
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
      axios.get('/modules/reports/fleet_reports/most_expensive_vehicle/most_expensive_vehicle_data.php', {
        params: { action: 'report', from: this.from, to: this.to },
      })
      .then(r => { this.rows = r.data; this.ran = true; })
      .catch(() => { this.error = 'Failed to load data.'; })
      .finally(() => { this.loading = false; });
    },
  },
}).mount('#most-exp-veh-app');
