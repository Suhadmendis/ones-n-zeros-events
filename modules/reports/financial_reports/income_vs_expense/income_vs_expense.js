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
        income:   this.rows.reduce((s, r) => s + parseFloat(r.income || 0), 0),
        expenses: this.rows.reduce((s, r) => s + parseFloat(r.expenses || 0), 0),
        net:      this.rows.reduce((s, r) => s + parseFloat(r.net || 0), 0),
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
        .get('/modules/reports/financial_reports/income_vs_expense/income_vs_expense_data.php?action=report&year=' + this.year)
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
      const el = document.querySelector('#incExpChart');
      if (!el) return;
      this.chart = new ApexCharts(el, {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        series: [
          { name: 'Income',   data: this.rows.map(r => r.income) },
          { name: 'Expenses', data: this.rows.map(r => r.expenses) },
        ],
        xaxis: { categories: this.rows.map(r => r.month_label) },
        colors: ['#10b981', '#ef4444'],
        plotOptions: { bar: { columnWidth: '60%' } },
        yaxis: { labels: { formatter: v => 'LKR ' + Number(v).toLocaleString() } },
      });
      this.chart.render();
    },
  },
}).mount('#inc-exp-app');
