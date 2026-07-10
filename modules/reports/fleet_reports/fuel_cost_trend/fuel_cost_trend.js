const { createApp, nextTick } = Vue;

createApp({
  data() {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let y = currentYear; y >= currentYear - 4; y--) years.push(y);
    return {
      filters: { year: currentYear, vehicle_id: 'all' },
      years,
      vehicles: [],
      rows: [],
      loading: false,
      error: null,
      chart: null,
    };
  },
  computed: {
    totalLitres() {
      return this.rows.reduce((s, r) => s + parseFloat(r.total_litres || 0), 0);
    },
    totalCost() {
      return this.rows.reduce((s, r) => s + parseFloat(r.total_cost || 0), 0);
    },
  },
  methods: {
    fmt: ReportUtils.fmt,
    async loadVehicles() {
      try {
        const res = await fetch('/modules/reports/fleet_reports/fuel_cost_trend/fuel_cost_trend_data.php?action=list_vehicles');
        this.vehicles = await res.json();
      } catch (e) { /* ignore */ }
    },
    async load() {
      this.loading = true;
      this.error   = null;
      this.rows    = [];
      if (this.chart) { this.chart.destroy(); this.chart = null; }
      try {
        const params = new URLSearchParams({
          action: 'report',
          year: this.filters.year,
          vehicle_ref: this.filters.vehicle_id,
        });
        const res = await fetch(`/modules/reports/fleet_reports/fuel_cost_trend/fuel_cost_trend_data.php?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        this.rows = data.rows || [];
        await nextTick();
        this.renderChart();
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },
    renderChart() {
      const el = document.querySelector('#fuelTrendChart');
      if (!el || !this.rows.length) return;
      this.chart = new ApexCharts(el, {
        chart: { type: 'line', height: 280, toolbar: { show: false } },
        series: [{ name: 'Fuel Cost (LKR)', data: this.rows.map(r => parseFloat(r.total_cost || 0)) }],
        xaxis: { categories: this.rows.map(r => r.month_label) },
        yaxis: { labels: { formatter: v => 'LKR ' + Number(v).toLocaleString() } },
        colors: ['#f59e0b'],
        stroke: { curve: 'smooth', width: 2 },
        tooltip: { y: { formatter: v => 'LKR ' + Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2 }) } },
      });
      this.chart.render();
    },
  },
  mounted() {
    this.loadVehicles().then(() => this.load());
  },
}).mount('#fuel-trend-app');
