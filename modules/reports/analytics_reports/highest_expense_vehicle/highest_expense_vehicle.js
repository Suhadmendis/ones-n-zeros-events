ReportUtils.createReport({
  dataUrl: '/modules/reports/analytics_reports/highest_expense_vehicle/highest_expense_vehicle_data.php',
  mountId: '#high-exp-veh-app',
  renderChart() {
    if (this.chart) { this.chart.destroy(); this.chart = null; }
    this.chart = new ApexCharts(document.querySelector('#highExpVehChart'), {
      chart: { type: 'bar', stacked: true, height: 280, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true } },
      series: [
        { name: 'Fuel', data: this.rows.map(r => r.fuel_cost) },
        { name: 'Maintenance', data: this.rows.map(r => r.maintenance_cost) },
      ],
      xaxis: { categories: this.rows.map(r => r.plate_number) },
      colors: ['#f59e0b', '#ef4444'],
    });
    this.chart.render();
  },
});
