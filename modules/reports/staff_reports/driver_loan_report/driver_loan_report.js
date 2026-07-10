const { createApp } = Vue;

createApp({
  data() {
    return {
      driver_id: '',
      status: 'all',
      drivers: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      // Effective permission matrix for this module, injected by home.php
      // (server/general/access_engine.php). Falls back to permissive when
      // absent so this stays non-disruptive ahead of Stage 3's server-side
      // enforcement — see the access-engine plan.
      perms: window.__MODULE_PERMS__ || null,
    };
  },
  computed: {
    canExport() { return this.perms ? !!this.perms.effective_can_export : true; },
    canPrint()  { return this.perms ? !!this.perms.effective_can_print  : true; },
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/driver_loan_report/driver_loan_report_data.php?action=list_drivers')
      .then(r => { this.drivers = r.data; })
      .catch(() => {});
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      this.loading = true;
      this.error = '';
      const params = new URLSearchParams({ action: 'report', status: this.status });
      if (this.driver_id) params.set('driver_ref', this.driver_id);
      axios.get('/modules/reports/staff_reports/driver_loan_report/driver_loan_report_data.php?' + params.toString())
        .then(r => { this.rows = r.data.rows; this.ran = true; })
        .catch(() => { this.error = 'Failed to load report.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#driver-loan-app');
