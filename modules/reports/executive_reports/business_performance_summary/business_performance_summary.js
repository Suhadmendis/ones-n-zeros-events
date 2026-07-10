const { createApp } = Vue;

createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      filters: { from, to },
      data: null,
      loading: false,
      error: null,
      months: ReportUtils.buildMonths(),
    };
  },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.filters.from = from;
      this.filters.to   = to;
      this.load();
    },
    async load() {
      this.loading = true;
      this.error   = null;
      this.data    = null;
      try {
        const params = new URLSearchParams({ action: 'summary', from: this.filters.from, to: this.filters.to });
        const res = await fetch(`/modules/reports/executive_reports/business_performance_summary/business_performance_summary_data.php?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (json.warning) { this.error = 'Data error: ' + json.warning; return; }
        this.data = json;
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },
  },
  mounted() {
    this.load();
  },
}).mount('#biz-perf-app');
