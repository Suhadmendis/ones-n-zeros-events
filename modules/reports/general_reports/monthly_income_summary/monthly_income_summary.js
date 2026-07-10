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
    };
  },
  computed: {
    totals() {
      return {
        trips: this.rows.reduce((s, r) => s + parseInt(r.trip_count || 0, 10), 0),
        revenue: this.rows.reduce((s, r) => s + parseFloat(r.revenue || 0), 0),
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
        .get('/modules/reports/general_reports/monthly_income_summary/monthly_income_summary_data.php?action=report&year=' + this.year)
        .then(r => {
          this.rows = r.data;
          this.ran = true;
        })
        .catch(() => {
          this.error = 'Failed to load data.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
}).mount('#monthly-income-app');
