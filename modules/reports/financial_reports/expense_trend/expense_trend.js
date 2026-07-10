const { createApp } = Vue;
createApp({
  data() {
    const now = new Date();
    return {
      year: now.getFullYear(),
      years: Array.from({ length: 5 }, (_, i) => now.getFullYear() - i),
      loading: false,
      ran: false,
      error: '',
      rows: [],
      chart: null,
    };
  },
  computed: {
    totals() {
      return {
        fuel:        this.rows.reduce((s, r) => s + parseFloat(r.fuel || 0), 0),
        vehicle_exp: this.rows.reduce((s, r) => s + parseFloat(r.vehicle_exp || 0), 0),
        general_exp: this.rows.reduce((s, r) => s + parseFloat(r.general_exp || 0), 0),
        advances:    this.rows.reduce((s, r) => s + parseFloat(r.advances || 0), 0),
        total:       this.rows.reduce((s, r) => s + parseFloat(r.total || 0), 0),
      };
    },
  },
  mounted() {
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      this.loading = true;
      this.error = '';
      axios
        .get('/modules/reports/financial_reports/expense_trend/expense_trend_data.php?action=report&year=' + this.year)
        .then(r => {
          this.rows = r.data;
          this.ran  = true;
          this.$nextTick(() => this.renderChart());
        })
        .catch(() => {
          this.error = 'Failed to load data.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
    renderChart() {
      if (this.chart) { this.chart.destroy(); this.chart = null; }
      const el = document.querySelector('#expTrendChart');
      if (!el) return;
      this.chart = new ApexCharts(el, {
        chart: { type: 'bar', stacked: true, height: 300, toolbar: { show: false } },
        series: [
          { name: 'Fuel',     data: this.rows.map(r => r.fuel) },
          { name: 'Vehicle',  data: this.rows.map(r => r.vehicle_exp) },
          { name: 'General',  data: this.rows.map(r => r.general_exp) },
          { name: 'Advances', data: this.rows.map(r => r.advances) },
        ],
        xaxis: { categories: this.rows.map(r => r.month_label) },
        colors: ['#f59e0b', '#ef4444', '#6b7280', '#3b82f6'],
        yaxis: { labels: { formatter: v => 'LKR ' + Number(v).toLocaleString() } },
        tooltip: { y: { formatter: v => 'LKR ' + parseFloat(v).toLocaleString('en-LK', { minimumFractionDigits: 2 }) } },
      });
      this.chart.render();
    },
  },
}).mount('#exp-trend-app');
