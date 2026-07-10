ReportUtils.createReport({
  dataUrl: '/modules/reports/analytics_reports/top_performing_driver/top_performing_driver_data.php',
  mountId: '#top-driver-app',
  extraData: { limit: '10' },
  renderChart() {
    if (this.chart) { this.chart.destroy(); this.chart = null; }
    this.chart = new ApexCharts(document.querySelector('#topDriverChart'), {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true } },
      series: [{ name: 'Total Revenue (LKR)', data: this.rows.map(r => r.total_revenue) }],
      xaxis: { categories: this.rows.map(r => r.name) },
      colors: ['#3b82f6'],
      yaxis: { labels: { formatter: v => 'LKR ' + Number(v).toLocaleString() } },
    });
    this.chart.render();
  },
});
