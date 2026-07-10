// payroll_runs.js — header (Payroll Run) + nested Employee Payroll rows, each with its own
// nested Salary Component lines. Hand-built (Generate Module only scaffolds flat single tables);
// pattern follows modules/operations/work_orders/work_order_entries/work_order_entries.js.

const { createApp } = Vue;

const lineHasData = (l) => (l.salary_component_ref || l.quantity || l.rate || l.amount);
const employeeHasData = (e) => (e.employment_record_ref || e.basic_salary || e.gross_earnings || e.total_deductions || e.net_salary || e.remarks || e.lines.some(lineHasData));

// Standard master-file picker field: Ref + Name (both read-only) + search + clear.
const PickerField = {
  props: ['label', 'placeholder', 'refValue', 'nameValue'],
  emits: ['open', 'clear'],
  template: `
    <label class="form-label small text-muted mb-1">{{ label }}</label>
    <div class="input-group input-group-sm">
      <input type="text" class="form-control font-monospace" :value="refValue" placeholder="Ref…" readonly style="max-width:90px" />
      <input type="text" class="form-control" :value="nameValue" :placeholder="placeholder" readonly />
      <button class="btn btn-outline-primary" type="button" @click="$emit('open')" title="Pick">
        <i class="bi bi-search"></i>
      </button>
      <button class="btn btn-outline-secondary" type="button" @click="$emit('clear')" v-if="refValue">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  `,
};

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading: false,
      saving:  false,
      saved:   false,
      generating: false,
      cancelling: false,
      error:   '',
      isExisting: false,
      skipped: [],
      pickerEmployeeIndex: null,
      pickerLineIndex: null,
      form: {
        ref: '',
        payroll_period_ref: '',
        payroll_period_name: '',
        run_date: '',
        description: '',
        payroll_status: '',
        total_gross_amount: 0,
        total_deduction_amount: 0,
        total_net_amount: 0,
        remarks: '',
        employees: [],
      },
    };
  },

  computed: {
    isDirty() {
      return (
        this.form.payroll_period_ref !== '' ||
        this.form.run_date !== '' ||
        this.form.description !== '' ||
        this.form.payroll_status !== '' ||
        this.form.total_gross_amount !== 0 ||
        this.form.total_deduction_amount !== 0 ||
        this.form.total_net_amount !== 0 ||
        this.form.remarks !== '' ||
        this.form.employees.some(employeeHasData)
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('payroll_runs-selected', (e) => this.loadRecord(e.detail));
    document.addEventListener('pr-payroll-period-selected', (e) => {
      this.form.payroll_period_ref  = e.detail.ref;
      this.form.payroll_period_name = e.detail.name;
      this.form.employees = [];
      this.skipped = [];
      this.form.total_gross_amount = 0;
      this.form.total_deduction_amount = 0;
      this.form.total_net_amount = 0;
    });
    document.addEventListener('pr-employment-record-selected', (e) => {
      if (this.pickerEmployeeIndex === null) return;
      const emp = this.form.employees[this.pickerEmployeeIndex];
      if (!emp) return;
      emp.employment_record_ref  = e.detail.ref;
      emp.employment_record_name = e.detail.m_employees?.full_name ?? e.detail.ref;
      this.pickerEmployeeIndex = null;
    });
    document.addEventListener('pr-salary-component-selected', (e) => {
      if (this.pickerEmployeeIndex === null || this.pickerLineIndex === null) return;
      const emp = this.form.employees[this.pickerEmployeeIndex];
      const line = emp && emp.lines[this.pickerLineIndex];
      if (!line) return;
      line.salary_component_ref  = e.detail.ref;
      line.salary_component_name = e.detail.name;
      line.component_type        = e.detail.component_type ?? '';
      this.pickerEmployeeIndex = null;
      this.pickerLineIndex = null;
    });
  },

  methods: {
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    clearPayrollPeriod() {
      this.form.payroll_period_ref = '';
      this.form.payroll_period_name = '';
      this.form.employees = [];
      this.skipped = [];
      this.form.total_gross_amount = 0;
      this.form.total_deduction_amount = 0;
      this.form.total_net_amount = 0;
    },

    clearEmploymentRecordField(ei) {
      this.form.employees[ei].employment_record_ref = '';
      this.form.employees[ei].employment_record_name = '';
    },
    clearLineField(ei, li) {
      this.form.employees[ei].lines[li].salary_component_ref = '';
      this.form.employees[ei].lines[li].salary_component_name = '';
      this.form.employees[ei].lines[li].component_type = '';
    },

    removeEmployee(i)  { this.form.employees.splice(i, 1); },
    removeLine(ei, li) { this.form.employees[ei].lines.splice(li, 1); },

    onGeneratePayroll() {
      if (!this.form.payroll_period_ref) return;
      this.generating = true;
      this.saved = false;
      this.error = '';
      axios.get('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_data.php?action=generate&payroll_period_ref=' + encodeURIComponent(this.form.payroll_period_ref))
        .then(res => {
          const data = res.data;
          if (data.error) { this.error = data.error; return; }
          this.skipped = data.skipped || [];
          this.form.total_gross_amount = data.total_gross_amount ?? 0;
          this.form.total_deduction_amount = data.total_deduction_amount ?? 0;
          this.form.total_net_amount = data.total_net_amount ?? 0;
          this.form.employees = (data.employees || []).map(e => ({
            employment_record_ref: e.employment_record_ref ?? '',
            employment_record_name: e.employment_record_name ?? '',
            basic_salary: e.basic_salary ?? 0,
            gross_earnings: e.gross_earnings ?? 0,
            total_deductions: e.total_deductions ?? 0,
            net_salary: e.net_salary ?? 0,
            payment_status: e.payment_status || 'Pending',
            paid_date: e.paid_date || '',
            remarks: e.remarks || '',
            lines: (e.lines || []).map(l => ({
              salary_component_ref: l.salary_component_ref ?? '',
              salary_component_name: l.salary_component_name ?? '',
              component_type: l.component_type ?? '',
              quantity: l.quantity ?? 0,
              rate: l.rate ?? 0,
              amount: l.amount ?? 0,
            })),
          }));
          if (!this.form.employees.length && !this.skipped.length) {
            this.error = 'No eligible employees found for this payroll period.';
          }
        })
        .catch(err => { this.error = 'Failed to generate payroll.'; console.error(err); })
        .finally(() => { this.generating = false; });
    },

    openEmploymentRecordPicker(ei) {
      this.pickerEmployeeIndex = ei;
      new bootstrap.Modal(document.getElementById('prEmploymentRecordPickerModal')).show();
    },
    openSalaryComponentPicker(ei, li) {
      this.pickerEmployeeIndex = ei;
      this.pickerLineIndex = li;
      new bootstrap.Modal(document.getElementById('prSalaryComponentPickerModal')).show();
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref: this.form.ref,
            payroll_period_name: String(this.form.payroll_period_name ?? ''),
            run_date: String(this.form.run_date ?? ''),
            description: String(this.form.description ?? ''),
            payroll_status: String(this.form.payroll_status ?? ''),
            total_gross_amount: String(this.form.total_gross_amount ?? ''),
            total_deduction_amount: String(this.form.total_deduction_amount ?? ''),
            total_net_amount: String(this.form.total_net_amount ?? ''),
            remarks: String(this.form.remarks ?? ''),
            employees: JSON.stringify(this.form.employees.filter(employeeHasData)),
          });
          window.open('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() {
      if (!this.form.ref) { this.error = 'No record to cancel. Search and select a saved payroll run first.'; return; }
      this.error = '';
      this.saved = false;
      axios.get('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to cancel.';
            return;
          }
          if (this.form.payroll_status === 'Cancelled') { this.error = 'This payroll run is already cancelled.'; return; }
          if (!confirm('Cancel this payroll run? This cannot be undone.')) return;

          this.cancelling = true;
          axios.post('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_data.php?action=cancel', { ref: this.form.ref })
            .then(res2 => {
              if (res2.data.error) { this.error = res2.data.error; return; }
              this.form.payroll_status = 'Cancelled';
              this.saved = true;
            })
            .catch(err => { this.error = 'Failed to cancel payroll run.'; console.error(err); })
            .finally(() => { this.cancelling = false; });
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onClose()  { this.onReset(); },

    onReset() {
      this.saved = false;
      this.error = '';
      this.isExisting = false;
      this.skipped = [];
      this.form.payroll_period_ref = '';
      this.form.payroll_period_name = '';
      this.form.run_date = '';
      this.form.description = '';
      this.form.payroll_status = '';
      this.form.total_gross_amount = 0;
      this.form.total_deduction_amount = 0;
      this.form.total_net_amount = 0;
      this.form.remarks = '';
      this.form.employees = [];
      this.pickerEmployeeIndex = null;
      this.pickerLineIndex = null;
    },

    onSave() {
      if (!this.isDirty) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      const payload = {
        ...this.form,
        employees: this.form.employees
          .filter(employeeHasData)
          .map(e => ({ ...e, lines: e.lines.filter(lineHasData) })),
      };

      const proceed = (action) => {
        axios.post('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_data.php?action=' + action, payload)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/human_resources_payroll/payroll_processing/payroll_runs/payroll_runs_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },

    loadRecord(data) {
      this.form.ref = data.ref;
      this.form.payroll_period_ref = data.payroll_period_ref ?? '';
      this.form.payroll_period_name = data.m_payroll_periods?.name ?? '';
      this.form.run_date = data.run_date;
      this.form.description = data.description;
      this.form.payroll_status = data.payroll_status;
      this.form.total_gross_amount = data.total_gross_amount;
      this.form.total_deduction_amount = data.total_deduction_amount;
      this.form.total_net_amount = data.total_net_amount;
      this.form.remarks = data.remarks;
      this.form.employees = (data.employees && data.employees.length)
        ? data.employees.map(e => ({
            employment_record_ref: e.employment_record_ref ?? '',
            employment_record_name: e.m_employment_records?.m_employees?.full_name ?? '',
            basic_salary: e.basic_salary ?? 0,
            gross_earnings: e.gross_earnings ?? 0,
            total_deductions: e.total_deductions ?? 0,
            net_salary: e.net_salary ?? 0,
            payment_status: e.payment_status ?? '',
            paid_date: e.paid_date ?? '',
            remarks: e.remarks ?? '',
            lines: (e.lines && e.lines.length)
              ? e.lines.map(l => ({
                  salary_component_ref: l.salary_component_ref ?? '',
                  salary_component_name: l.m_salary_components?.name ?? '',
                  component_type: l.component_type ?? '',
                  quantity: l.quantity ?? 0,
                  rate: l.rate ?? 0,
                  amount: l.amount ?? 0,
                }))
              : [],
          }))
        : [];
      this.skipped = [];
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },
  },
})
  .component('picker-field', PickerField)
  .mount('#payroll-runs-app');
