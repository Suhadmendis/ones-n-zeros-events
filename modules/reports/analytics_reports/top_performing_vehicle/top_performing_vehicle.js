ReportUtils.createReport({
  dataUrl: '/modules/reports/analytics_reports/top_performing_vehicle/top_performing_vehicle_data.php',
  mountId: '#top-veh-app',
  extraData: { limit: '10' },
  renderChart() {
    if (this.chart) { this.chart.destroy(); this.chart = null; }
    this.chart = new ApexCharts(document.querySelector('#topVehChart'), {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true } },
      series: [{ name: 'Profit (LKR)', data: this.rows.map(r => r.profit) }],
      xaxis: { categories: this.rows.map(r => r.plate_number) },
      colors: ['#10b981'],
      yaxis: { labels: { formatter: v => 'LKR ' + Number(v).toLocaleString() } },
    });
    this.chart.render();
  },
});
