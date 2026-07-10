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
      grandTotal: 0,
      chart: null,
      months: ReportUtils.buildMonths(),
    };
  },
  computed: {
    totalTrips() {
      return this.rows.reduce((s, r) => s + (r.trip_count || 0), 0);
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
    renderChart() {
      if (this.chart) { this.chart.destroy(); this.chart = null; }
      const labels  = this.rows.map(r => r.department);
      const series  = this.rows.map(r => parseFloat(r.total_revenue || 0));
      this.$nextTick(() => {
        const el = document.querySelector('#deptRevenueChart');
        if (!el) return;
        this.chart = new ApexCharts(el, {
          chart:  { type: 'donut', height: 280 },
          labels: labels,
          series: series,
          legend: { position: 'bottom' },
          tooltip: {
            y: { formatter: v => 'LKR ' + v.toLocaleString('en-LK', { minimumFractionDigits: 2 }) },
          },
        });
        this.chart.render();
      });
    },
    load() {
      if (!this.from || !this.to) { this.error = 'Please select both dates.'; return; }
      this.loading = true;
      this.error = '';
      axios.get('/modules/reports/operations_reports/department_revenue/department_revenue_data.php', {
        params: { action: 'report', from: this.from, to: this.to },
      })
      .then(r => {
        this.rows       = r.data.rows  || [];
        this.grandTotal = r.data.total || 0;
        this.ran = true;
        if (this.rows.length) this.renderChart();
      })
      .catch(() => { this.error = 'Failed to load data.'; })
      .finally(() => { this.loading = false; });
    },
  },
}).mount('#dept-revenue-app');
