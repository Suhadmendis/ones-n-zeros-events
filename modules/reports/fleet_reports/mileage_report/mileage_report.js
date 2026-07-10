const { createApp } = Vue;

createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      filters: { from, to, vehicle_id: 'all' },
      vehicles: [],
      result: null,
      loading: false,
      error: null,
      months: ReportUtils.buildMonths(),
    };
  },
  methods: {
    async loadVehicles() {
      try {
        const res = await fetch('/modules/reports/fleet_reports/mileage_report/mileage_report_data.php?action=list_vehicles');
        this.vehicles = await res.json();
      } catch (e) { /* ignore */ }
    },
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.filters.from = from;
      this.filters.to   = to;
      this.load();
    },
    async load() {
      this.loading = true;
      this.error   = null;
      this.result  = null;
      try {
        const params = new URLSearchParams({
          action: 'report',
          from: this.filters.from,
          to: this.filters.to,
          vehicle_ref: this.filters.vehicle_id,
        });
        const res = await fetch(`/modules/reports/fleet_reports/mileage_report/mileage_report_data.php?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        this.result = await res.json();
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },
  },
  mounted() {
    this.loadVehicles().then(() => this.load());
  },
}).mount('#mileage-app');
