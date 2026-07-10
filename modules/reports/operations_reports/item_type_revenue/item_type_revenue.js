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
    load() {
      if (!this.from || !this.to) { this.error = 'Please select both dates.'; return; }
      this.loading = true;
      this.error = '';
      axios.get('/modules/reports/operations_reports/item_type_revenue/item_type_revenue_data.php', {
        params: { action: 'report', from: this.from, to: this.to },
      })
      .then(r => { this.rows = r.data.rows || []; this.grandTotal = r.data.total || 0; this.ran = true; })
      .catch(() => { this.error = 'Failed to load data.'; })
      .finally(() => { this.loading = false; });
    },
  },
}).mount('#item-revenue-app');
