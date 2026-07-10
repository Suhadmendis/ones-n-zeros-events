const { createApp } = Vue;
createApp({
  data() {
    const { from: dateFrom, to: dateTo } = ReportUtils.currentMonthRange();
    return {
      dateFrom,
      dateTo,
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
        litres: this.rows.reduce((s,r)=>s+parseFloat(r.total_litres||0),0),
        km:     this.rows.reduce((s,r)=>s+parseFloat(r.total_km||0),0),
        cost:   this.rows.reduce((s,r)=>s+parseFloat(r.fuel_cost||0),0),
      };
    },
  },
  mounted() { this.load(); },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.dateFrom = from;
      this.dateTo   = to;
      this.load();
    },
    kplClass(v) {
      if (v === null || v === undefined) return '';
      if (v >= 8) return 'text-success fw-bold';
      if (v >= 5) return 'text-warning fw-bold';
      return 'text-danger fw-bold';
    },
    load() {
      this.loading = true; this.error = '';
      axios.get('/modules/reports/general_reports/fuel_usage_report/fuel_usage_report_data.php?action=report&from='+this.dateFrom+'&to='+this.dateTo)
        .then(r => { this.rows = r.data; this.ran = true; })
        .catch(() => this.error = 'Failed to load report.')
        .finally(() => this.loading = false);
    },
  },
}).mount('#fuel-usage-app');
