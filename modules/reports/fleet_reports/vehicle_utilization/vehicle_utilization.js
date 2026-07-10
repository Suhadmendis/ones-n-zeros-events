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
        const res = await fetch(`/modules/reports/fleet_reports/vehicle_utilization/vehicle_utilization_data.php?${params}`);
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
}).mount('#veh-util-app');
