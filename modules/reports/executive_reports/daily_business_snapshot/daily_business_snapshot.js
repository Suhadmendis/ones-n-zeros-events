const { createApp } = Vue;

createApp({
  data() {
    return {
      filters: { date: (() => { const n = new Date(); return `${n.getFullYear()}-${String(n.getMonth()+1).padStart(2,'0')}-${String(n.getDate()).padStart(2,'0')}`; })() },
      data: null,
      loading: false,
      error: null,
    };
  },
  methods: {
    fmt: ReportUtils.fmt,
    async load() {
      this.loading = true;
      this.error   = null;
      this.data    = null;
      try {
        const params = new URLSearchParams({ action: 'snapshot', date: this.filters.date });
        const res = await fetch(`/modules/reports/executive_reports/daily_business_snapshot/daily_business_snapshot_data.php?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        this.data = await res.json();
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
}).mount('#daily-snapshot-app');
