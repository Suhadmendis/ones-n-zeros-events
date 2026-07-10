const { createApp } = Vue;
createApp({
  data() {
    const { from: dateFrom, to: dateTo } = ReportUtils.currentMonthRange();
    return {
      dateFrom,
      dateTo,
      cleanerId: '',
      cleaners: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: {},
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    axios.get('/modules/reports/general_reports/cleaner_salary_list/cleaner_salary_list_data.php?action=list_cleaners').then(r => { this.cleaners = r.data; });
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.dateFrom = from;
      this.dateTo   = to;
      this.load();
    },
    load() {
      this.loading = true; this.error = '';
      let url = '/modules/reports/general_reports/cleaner_salary_list/cleaner_salary_list_data.php?action=report&from='+this.dateFrom+'&to='+this.dateTo;
      if (this.cleanerId) url += '&cleaner_ref='+this.cleanerId;
      axios.get(url)
        .then(r => { this.rows = r.data.rows; this.summary = r.data.summary; this.ran = true; })
        .catch(() => this.error = 'Failed to load report.')
        .finally(() => this.loading = false);
    },
  },
}).mount('#cleaner-salary-list-app');
