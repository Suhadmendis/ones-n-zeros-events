const { createApp } = Vue;

createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      filters: { from, to },
      result: null,
      loading: false,
      error: null,
      months: ReportUtils.buildMonths(),
    };
  },
  methods: {
    kplClass(v) {
      if (v === null) return '';
      if (v >= 8) return 'text-success fw-bold';
      if (v >= 5) return 'text-warning fw-bold';
      return 'text-danger fw-bold';
    },
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.filters.from = from;
      this.filters.to   = to;
      this.load();
    },
    async load() {
      this.loading = true;
      this.error   = null;
      this.result  = null;
      try {
        const params = new URLSearchParams({ action: 'report', from: this.filters.from, to: this.filters.to });
        const res = await fetch(`/modules/reports/fleet_reports/fuel_efficiency/fuel_efficiency_data.php?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        this.result = await res.json();
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
}).mount('#fuel-eff-app');
