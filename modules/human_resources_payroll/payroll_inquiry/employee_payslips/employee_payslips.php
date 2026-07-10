<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Employee Payslips
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          <strong>Employee Payslips</strong> looks up an employee's payslip for a payroll period that has
          already been processed through <strong>Payroll Runs</strong>. It answers:
          <em>"What was this employee paid for this period, and how was it broken down?"</em>
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report is read-only and draws from tables populated by Payroll Runs:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>m_payroll_runs</code></td><td>Finds the run processed for the selected period</td></tr>
            <tr><td><code>t_employee_payrolls</code></td><td>The employee's totals within that run</td></tr>
            <tr><td><code>t_employee_payroll_lines</code></td><td>Salary component breakdown for that employee</td></tr>
          </tbody>
        </table>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> If nothing shows up, the period hasn't been processed in Payroll Runs yet, or this employee wasn't included in that run.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="employee-payslips-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Employee <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" v-model="employee_ref" style="min-width:200px">
            <option value="">— Select Employee —</option>
            <option v-for="e in employees" :key="e.ref" :value="e.ref">{{ e.full_name }} ({{ e.ref }})</option>
          </select>
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Payroll Period <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" v-model="payroll_period_ref" style="min-width:180px">
            <option value="">— Select Period —</option>
            <option v-for="p in periods" :key="p.ref" :value="p.ref">{{ p.name }}</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading || !employee_ref || !payroll_period_ref">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Generate Payslip
          </button>
        </div>
        <div class="col-auto ms-auto">
          <div class="report-export-btns d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" onclick="ReportUtils.exportExcel()">
              <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ReportUtils.printReport()">
              <i class="bi bi-printer me-1"></i>Print / PDF
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#reportInfoModal" title="How this report works">
              <i class="bi bi-info-circle me-1"></i>How this report works
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Placeholder -->
  <div class="card border-dashed" v-if="!ran">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-receipt fs-1 d-block mb-2"></i>
      Select an employee and payroll period to generate a payslip
    </div>
  </div>

  <div class="alert alert-warning mt-2" v-if="ran && error">{{ error }}</div>

  <!-- Payslip card -->
  <div class="card" v-if="ran && slip" style="max-width:600px">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-bold fs-5">{{ slip.employee_name }}</div>
        <div class="small opacity-75">Ref: {{ slip.employee_ref }}</div>
      </div>
      <div class="text-end">
        <div class="small opacity-75">Payslip</div>
        <div class="fw-semibold">{{ slip.payroll_period_name }}</div>
      </div>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <div class="text-uppercase text-muted small fw-bold mb-2 border-bottom pb-1">Salary Components</div>
        <div class="d-flex justify-content-between py-1" v-for="(line, i) in slip.lines" :key="i">
          <span>{{ line.component_name }} <small class="text-muted" v-if="line.quantity">(qty {{ line.quantity }} &times; {{ fmt(line.rate) }})</small></span>
          <span>LKR {{ fmt(line.amount) }}</span>
        </div>
        <div class="d-flex justify-content-between py-1" v-if="!slip.lines.length">
          <span class="text-muted">Basic Salary</span>
          <span>LKR {{ fmt(slip.basic_salary) }}</span>
        </div>
        <div class="d-flex justify-content-between py-1 fw-bold border-top mt-1 pt-2">
          <span>Gross Earnings</span>
          <span>LKR {{ fmt(slip.gross_earnings) }}</span>
        </div>
      </div>

      <div class="mb-3">
        <div class="text-uppercase text-muted small fw-bold mb-2 border-bottom pb-1">Deductions</div>
        <div class="d-flex justify-content-between py-1 fw-bold">
          <span>Total Deductions</span>
          <span class="text-danger">&minus; LKR {{ fmt(slip.total_deductions) }}</span>
        </div>
      </div>

      <div class="border-top pt-3 mt-2">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fw-bold fs-5">Net Payable</span>
          <span class="fw-bold fs-4" :class="slip.net_salary >= 0 ? 'text-success' : 'text-danger'">
            LKR {{ fmt(slip.net_salary) }}
          </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
          <span>Payment Status</span>
          <span>{{ slip.payment_status }}<span v-if="slip.paid_date"> &mdash; {{ slip.paid_date }}</span></span>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="/modules/human_resources_payroll/payroll_inquiry/employee_payslips/employee_payslips.js"></script>
