const { createApp } = Vue;

createApp({
  data() {
    return {
      employee_ref: '',
      payroll_period_ref: '',
      employees: [],
      periods: [],
      loading: false,
      ran: false,
      error: '',
      slip: null,
    };
  },
  mounted() {
    axios.get('/modules/human_resources_payroll/payroll_inquiry/employee_payslips/employee_payslips_data.php?action=list_employees')
      .then(r => { this.employees = r.data; })
      .catch(() => {});
    axios.get('/modules/human_resources_payroll/payroll_inquiry/employee_payslips/employee_payslips_data.php?action=list_periods')
      .then(r => { this.periods = r.data; })
      .catch(() => {});
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      if (!this.employee_ref || !this.payroll_period_ref) return;
      this.loading = true;
      this.error = '';
      this.slip = null;
      const params = new URLSearchParams({ employee_ref: this.employee_ref, payroll_period_ref: this.payroll_period_ref });
      axios.get('/modules/human_resources_payroll/payroll_inquiry/employee_payslips/employee_payslips_data.php?' + params.toString())
        .then(r => {
          if (r.data.error) { this.error = r.data.error; }
          else { this.slip = r.data; }
          this.ran = true;
        })
        .catch(() => { this.error = 'Failed to load payslip.'; this.ran = true; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#employee-payslips-app');
