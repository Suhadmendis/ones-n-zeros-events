// general_ledger.js — General Ledger read-only viewer

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading:    false,
      searched:   false,

      // COA typeahead
      coaAccounts: [],
      acctSugg:    [],
      acctIdx:     -1,

      filters: {
        account_code:         '',
        account_name_display: '',
        period:               '',
        date_from:            '',
        date_to:              '',
        journal_ref:          '',
        status:               '',
        description:          '',
      },

      activeQuick: '',
      quickPeriods: [],

      entries:         [],
      openingBalance:  0,
      totals:          { total_count: 0, total_debit: 0, total_credit: 0 },
      page:            1,
      perPage:         50,
    };
  },

  computed: {
    totalPages() {
      return Math.max(1, Math.ceil(this.totals.total_count / this.perPage));
    },
    rangeStart() {
      return this.totals.total_count === 0 ? 0 : (this.page - 1) * this.perPage + 1;
    },
    rangeEnd() {
      return Math.min(this.page * this.perPage, this.totals.total_count);
    },
    netBalance() {
      return (this.totals.total_debit || 0) - (this.totals.total_credit || 0);
    },
    showBalance() {
      return this.filters.account_code.trim() !== '';
    },
    entriesWithBalance() {
      if (!this.showBalance) return this.entries;
      let running = this.openingBalance;
      return this.entries.map(r => {
        running += (parseFloat(r.debit_amount) || 0) - (parseFloat(r.credit_amount) || 0);
        return { ...r, _balance: running };
      });
    },
    pageTotalDebit() {
      return this.entries.reduce((s, r) => s + (parseFloat(r.debit_amount) || 0), 0);
    },
    pageTotalCredit() {
      return this.entries.reduce((s, r) => s + (parseFloat(r.credit_amount) || 0), 0);
    },
    paginationPages() {
      const total = this.totalPages;
      const cur   = this.page;
      if (total <= 9) return Array.from({ length: total }, (_, i) => i + 1);
      if (cur <= 5)   return [1, 2, 3, 4, 5, 6, '…', total];
      if (cur >= total - 4) return [1, '…', total - 5, total - 4, total - 3, total - 2, total - 1, total];
      return [1, '…', cur - 2, cur - 1, cur, cur + 1, cur + 2, '…', total];
    },
  },

  mounted() {
    this.fetchTitle();
    this.loadCoa();
    this.buildQuickPeriods();
  },

  methods: {

    /* ── Bootstrap ─────────────────────────────────────────── */
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => {});
    },

    loadCoa() {
      axios.get('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=list')
        .then(res => { this.coaAccounts = res.data || []; })
        .catch(() => {});
    },

    buildQuickPeriods() {
      const now = new Date();
      const y   = now.getFullYear();
      const m   = now.getMonth();
      const MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const qs  = [];

      // Last 13 months
      for (let i = 0; i < 13; i++) {
        const d  = new Date(y, m - i, 1);
        const ym = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        qs.push({ label: MON[d.getMonth()] + ' ' + d.getFullYear(), period: ym });
      }
      // Financial years
      qs.push({ label: 'FY ' + y,       date_from: y       + '-01-01', date_to: y       + '-12-31' });
      qs.push({ label: 'FY ' + (y - 1), date_from: (y - 1) + '-01-01', date_to: (y - 1) + '-12-31' });
      qs.push({ label: 'All Records',    clear: true });
      this.quickPeriods = qs;
    },

    /* ── Account code typeahead ────────────────────────────── */
    onAccountInput() {
      this.activeQuick = '';
      const val   = this.filters.account_code.trim();
      if (val === '') {
        this.filters.account_name_display = '';
        this.acctSugg = [];
        return;
      }
      const lower = val.toLowerCase();
      const exact = this.coaAccounts.find(a => a.account_code.toLowerCase() === lower);
      if (exact) {
        this.filters.account_name_display = exact.account_name;
        this.acctSugg = [];
        this.acctIdx  = -1;
        return;
      }
      this.filters.account_name_display = '';
      this.acctSugg = this.coaAccounts.filter(a =>
        a.account_code.toLowerCase().startsWith(lower) ||
        a.account_name.toLowerCase().includes(lower)
      ).slice(0, 12);
      this.acctIdx = -1;
    },
    acctNav(dir) {
      if (!this.acctSugg.length) return;
      this.acctIdx = dir === 'down'
        ? Math.min(this.acctIdx + 1, this.acctSugg.length - 1)
        : Math.max(this.acctIdx - 1, 0);
    },
    acctEnter() {
      if (this.acctSugg.length && this.acctIdx >= 0) this.pickAcct(this.acctSugg[this.acctIdx]);
    },
    pickAcct(s) {
      this.filters.account_code         = s.account_code;
      this.filters.account_name_display = s.account_name;
      this.acctSugg = [];
      this.acctIdx  = -1;
    },
    closeAccountSugg() {
      setTimeout(() => { this.acctSugg = []; this.acctIdx = -1; }, 150);
    },

    /* ── Filter helpers ────────────────────────────────────── */
    onPeriodChange() {
      this.filters.date_from = '';
      this.filters.date_to   = '';
      this.activeQuick       = '';
    },
    onDateChange() {
      this.filters.period = '';
      this.activeQuick    = '';
    },

    applyQuick(q) {
      this.activeQuick       = q.label;
      this.filters.period    = '';
      this.filters.date_from = '';
      this.filters.date_to   = '';
      if (q.clear) { this.applyFilters(); return; }
      if (q.period)    this.filters.period    = q.period;
      if (q.date_from) this.filters.date_from = q.date_from;
      if (q.date_to)   this.filters.date_to   = q.date_to;
      this.applyFilters();
    },

    applyFilters() {
      this.page = 1;
      this.fetchEntries();
    },

    clearFilters() {
      this.filters = {
        account_code: '', account_name_display: '', period: '',
        date_from: '', date_to: '', journal_ref: '', status: '', description: '',
      };
      this.activeQuick    = '';
      this.entries        = [];
      this.totals         = { total_count: 0, total_debit: 0, total_credit: 0 };
      this.openingBalance = 0;
      this.page           = 1;
      this.searched       = false;
    },

    goPage(p) {
      this.page = p;
      this.fetchEntries();
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    },

    /* ── Data fetch ────────────────────────────────────────── */
    fetchEntries() {
      this.loading  = true;
      this.searched = true;

      const params = new URLSearchParams({ action: 'filter', page: this.page, per_page: this.perPage });
      const { account_name_display, ...filterFields } = this.filters;
      Object.entries(filterFields).forEach(([k, v]) => { if (v !== '') params.set(k, v); });

      axios.get('/modules/finance/accounting/general_ledger/general_ledger_data.php?' + params.toString())
        .then(res => {
          const d = res.data;
          this.entries        = d.data         || [];
          this.openingBalance = d.opening_balance ?? 0;
          this.totals = {
            total_count:  d.total_count  || 0,
            total_debit:  d.total_debit  || 0,
            total_credit: d.total_credit || 0,
          };
        })
        .catch(err => { console.error('GL fetch error', err); })
        .finally(() => { this.loading = false; });
    },

    /* ── Actions ───────────────────────────────────────────── */
    jumpToJE(ref) {
      if (!ref) return;
      if (navigator.clipboard) navigator.clipboard.writeText(ref).catch(() => {});
      // Future: dispatch event to open JE module with this ref
    },

    exportCSV() {
      const headers = ['Date','GL Ref','Journal Ref','Account Code','Account Name','Description','Debit','Credit','Status'];
      const rows    = this.entries.map(r => [
        r.transaction_date,
        r.ref,
        r.journal_ref,
        r.account_code,
        '"' + (r.account_name || '').replace(/"/g, '""') + '"',
        '"' + (r.description  || '').replace(/"/g, '""') + '"',
        r.debit_amount,
        r.credit_amount,
        r.status,
      ]);
      const csv  = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
      const slug = this.filters.account_code || this.filters.period || 'export';
      const a    = Object.assign(document.createElement('a'), {
        href:     'data:text/csv;charset=utf-8,' + encodeURIComponent(csv),
        download: 'general_ledger_' + slug + '.csv',
      });
      a.click();
    },

    printView() { window.print(); },

    /* ── Formatters ────────────────────────────────────────── */
    fmtNum(v) {
      return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    fmtCount(v) {
      return Number(v || 0).toLocaleString('en-US');
    },
    fmtDate(v) {
      if (!v) return '';
      const [yr, mo, dy] = v.split('-');
      return dy + ' ' + ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][+mo - 1] + ' ' + yr;
    },
    statusBadge(s) {
      return { posted: 'text-bg-success', draft: 'text-bg-secondary', reversed: 'text-bg-danger' }[s] || 'text-bg-secondary';
    },
  },
}).mount('#gl-app');
