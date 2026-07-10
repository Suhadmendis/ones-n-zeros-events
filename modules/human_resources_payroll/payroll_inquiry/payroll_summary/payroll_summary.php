<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Payroll Summary
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          <strong>Payroll Summary</strong> shows the totals and employee-level breakdown of a payroll
          run that has already been processed through <strong>Payroll Runs</strong>, for a selected
          Payroll Period. It answers: <em>"How much did this payroll period cost, and what did each
          employee get paid?"</em>
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report is read-only and draws from tables populated by Payroll Runs:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>m_payroll_runs</code></td><td>The run processed for the selected period, and its saved totals</td></tr>
            <tr><td><code>t_employee_payrolls</code></td><td>Each employee's saved totals within that run</td></tr>
          </tbody>
        </table>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> If nothing shows up, the period hasn't been processed in Payroll Runs
          yet. This report never recalculates payroll — it only reads saved Payroll Run results.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="payroll-summary-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Payroll Period <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" v-model="payroll_period_ref" style="min-width:220px">
            <option value="">— Select Period —</option>
            <option v-for="p in periods" :key="p.ref" :value="p.ref">{{ p.name }}</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading || !payroll_period_ref">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Generate Report
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
      <i class="bi bi-file-earmark-spreadsheet fs-1 d-block mb-2"></i>
      Select a payroll period to generate the summary
    </div>
  </div>

  <div class="alert alert-warning mt-2" v-if="ran && error">{{ error }}</div>

  <div v-if="ran && !error && summary">
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Employees</div>
            <div class="fs-4 fw-bold">{{ summary.employee_count }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Total Gross Amount</div>
            <div class="fs-5 fw-bold text-success">LKR {{ fmt(summary.total_gross_amount) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Total Deduction Amount</div>
            <div class="fs-5 fw-bold text-danger">LKR {{ fmt(summary.total_deduction_amount) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Total Net Amount</div>
            <div class="fs-5 fw-bold text-primary">LKR {{ fmt(summary.total_net_amount) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ summary.payroll_period_name }}</strong>
        <span class="text-muted small">Run: {{ summary.payroll_run_ref }} &mdash; {{ summary.payroll_status }}</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th class="text-end">Basic Salary</th>
              <th class="text-end">Gross Earnings</th>
              <th class="text-end">Total Deductions</th>
              <th class="text-end">Net Salary</th>
              <th>Payment Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in summary.employees" :key="i">
              <td>{{ row.employee_name }}</td>
              <td class="text-end">{{ fmt(row.basic_salary) }}</td>
              <td class="text-end">{{ fmt(row.gross_earnings) }}</td>
              <td class="text-end text-danger">{{ fmt(row.total_deductions) }}</td>
              <td class="text-end fw-semibold">{{ fmt(row.net_salary) }}</td>
              <td>{{ row.payment_status }}</td>
            </tr>
            <tr v-if="!summary.employees.length">
              <td colspan="6" class="text-center text-muted py-3">No employees found in this payroll run.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="/modules/human_resources_payroll/payroll_inquiry/payroll_summary/payroll_summary.js"></script>
