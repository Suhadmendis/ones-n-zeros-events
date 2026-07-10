<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Salary Sheet
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Salary Sheet</strong> generates a detailed payslip for an individual driver for a
          selected month, showing earnings, deductions, and net payable. It answers:
          <em>"What is this driver's exact salary for the month after all deductions?"</em> — the primary
          tool for HR to process driver payroll.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>drivers</code></td><td><code>id, ref, name</code></td><td>Various</td><td>Driver identity on the payslip</td></tr>
            <tr><td><code>trips</code></td><td><code>driver_id, driver_salary, date</code></td><td>FK, NUMERIC, DATE</td><td>Trip earnings for the month</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>driver_id, amount, recipient_type</code></td><td>FK, NUMERIC, TEXT</td><td>Advances deducted from salary</td></tr>
            <tr><td><code>deductions</code></td><td><code>driver_id, amount, recipient_type</code></td><td>FK, NUMERIC, TEXT</td><td>Additional deductions from salary</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Select driver and month</strong> — The driver ID and month are used to scope all queries.</li>
          <li class="mb-1"><strong>Calculate gross earnings</strong> — Trip earnings (driver_salary) are summed from the trips table for the month.</li>
          <li class="mb-1"><strong>Calculate deductions</strong> — Advances (recipient_type = driver) and deductions records for the month are summed.</li>
          <li class="mb-1"><strong>Net payable</strong> — <code>net = gross − advances − deductions − loan_recovery</code>.</li>
          <li class="mb-1"><strong>Payslip display</strong> — Results are shown in a formatted payslip card with earnings and deductions sections.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> A driver must be selected before the payslip can be generated. Loan recovery is currently tracked as a total in the loans table, not per-month, so it shows as 0 here — adjust manually if needed.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-salary-sheet-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Month</label>
          <input type="month" class="form-control form-control-sm" v-model="month" />
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Driver <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm" v-model="driver_id" style="min-width:180px">
            <option value="">— Select Driver —</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }} ({{ d.ref }})</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading || !driver_id">
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
      <div class="row g-1 mt-2">
        <div class="col-12">
          <div class="d-flex flex-wrap gap-1 align-items-center">
            <small class="text-muted me-1">Jump to:</small>
            <button v-for="mn in months" :key="mn.month" type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="selectMonth(mn.year, mn.month)">{{ mn.label }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Placeholder -->
  <div class="card border-dashed" v-if="!driver_id && !ran">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-person-badge fs-1 d-block mb-2"></i>
      Select a driver to generate payslip
    </div>
  </div>

  <!-- Payslip card -->
  <div class="card" v-if="ran && slip" style="max-width:600px">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-bold fs-5">{{ slip.driver_name }}</div>
        <div class="small opacity-75">Ref: {{ slip.driver_ref }}</div>
      </div>
      <div class="text-end">
        <div class="small opacity-75">Payslip</div>
        <div class="fw-semibold">{{ slip.month }}</div>
      </div>
    </div>
    <div class="card-body">
      <!-- Earnings -->
      <div class="mb-3">
        <div class="text-uppercase text-muted small fw-bold mb-2 border-bottom pb-1">Earnings</div>
        <div class="d-flex justify-content-between py-1">
          <span>Trip Earnings <small class="text-muted">({{ slip.trip_count }} trips)</small></span>
          <span>LKR {{ fmt(slip.trip_earnings) }}</span>
        </div>
        <div class="d-flex justify-content-between py-1 fw-bold border-top mt-1 pt-2">
          <span>Gross Earnings</span>
          <span>LKR {{ fmt(slip.gross_earnings) }}</span>
        </div>
      </div>

      <!-- Deductions -->
      <div class="mb-3">
        <div class="text-uppercase text-muted small fw-bold mb-2 border-bottom pb-1">Deductions</div>
        <div class="d-flex justify-content-between py-1">
          <span>Advances</span>
          <span class="text-danger">− LKR {{ fmt(slip.advances) }}</span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span>Deductions</span>
          <span class="text-danger">− LKR {{ fmt(slip.deductions) }}</span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span>Loan Recovery</span>
          <span class="text-danger">− LKR {{ fmt(slip.loan_recovery) }}</span>
        </div>
        <div class="d-flex justify-content-between py-1 fw-bold border-top mt-1 pt-2">
          <span>Total Deductions</span>
          <span class="text-danger">− LKR {{ fmt(slip.advances + slip.deductions + slip.loan_recovery) }}</span>
        </div>
      </div>

      <!-- Net Payable -->
      <div class="border-top pt-3 mt-2">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fw-bold fs-5">Net Payable</span>
          <span class="fw-bold fs-4" :class="slip.net_salary >= 0 ? 'text-success' : 'text-danger'">
            LKR {{ fmt(slip.net_salary) }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/driver_salary_sheet/driver_salary_sheet.js"></script>
