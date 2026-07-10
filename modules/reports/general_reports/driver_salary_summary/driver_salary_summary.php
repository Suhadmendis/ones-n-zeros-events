<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Salary Summary
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Salary Summary</strong> displays all finalised salary settlements for drivers
          in a selected month — showing gross earnings, advances, deductions, loan recovery, net payable,
          and payment status. It answers:
          <em>"What salary settlements have been processed and paid this month?"</em> — the payroll
          overview for management sign-off.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>driver_salary_settlements</code></td><td><code>ref, month, driver_id, gross_earnings, advances, deductions_total, loan_recovery, net_payable, status</code></td><td>Various</td><td>One finalised settlement record per driver per month</td></tr>
            <tr><td><code>drivers</code></td><td><code>id, ref, name</code></td><td>Various</td><td>Joined to display driver name and reference</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by month</strong> — All settlement records for the selected month are fetched from the driver_salary_settlements table.</li>
          <li class="mb-1"><strong>Join drivers</strong> — Driver names and references are joined by driver_id.</li>
          <li class="mb-1"><strong>Compute summary KPIs</strong> — Settlement count, paid count, pending count, total gross, and total net payable are calculated.</li>
          <li class="mb-1"><strong>Display status badges</strong> — Each settlement shows a green "paid" or yellow "pending" badge.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> This report shows <strong>finalised settlements only</strong> — records must be entered into the driver_salary_settlements table first. For a live calculated payslip, use the Driver Salary Sheet report instead.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-salary-summary-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label mb-1">Month</label><input type="month" class="form-control form-control-sm" v-model="month" /></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm" @click="load" :disabled="loading"><span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Run Report</button></div>
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
  <div class="row g-3 mb-3" v-if="summary.settlement_count >= 0">
    <div class="col-md-3"><div class="card border-start border-primary border-3"><div class="card-body py-2"><div class="text-muted small">Settlements</div><div class="fs-5 fw-bold">{{ summary.settlement_count }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-start border-success border-3"><div class="card-body py-2"><div class="text-muted small">Paid</div><div class="fs-5 fw-bold text-success">{{ summary.paid_count }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-start border-warning border-3"><div class="card-body py-2"><div class="text-muted small">Pending</div><div class="fs-5 fw-bold text-warning">{{ summary.pending_count }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-start border-info border-3"><div class="card-body py-2"><div class="text-muted small">Total Net Payable</div><div class="fs-5 fw-bold">LKR {{ fmt(summary.total_net) }}</div></div></div></div>
  </div>
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark"><tr><th>Ref</th><th>Driver</th><th>Month</th><th class="text-end">Gross (LKR)</th><th class="text-end">Advances (LKR)</th><th class="text-end">Deductions (LKR)</th><th class="text-end">Loan Recovery (LKR)</th><th class="text-end">Net Payable (LKR)</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="r in rows">
            <td>{{ r.ref }}</td><td>{{ r.driver_name }} <small class="text-muted">({{ r.driver_ref }})</small></td><td>{{ r.month }}</td>
            <td class="text-end">{{ fmt(r.gross_earnings) }}</td><td class="text-end">{{ fmt(r.advances) }}</td>
            <td class="text-end">{{ fmt(r.deductions_total) }}</td><td class="text-end">{{ fmt(r.loan_recovery) }}</td>
            <td class="text-end fw-bold">{{ fmt(r.net_payable) }}</td>
            <td><span :class="'badge bg-'+(r.status==='paid'?'success':'warning text-dark')">{{ r.status }}</span></td>
          </tr>
          <tr v-if="!rows.length"><td colspan="9" class="text-center text-muted py-3">No settlements for selected month.</td></tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr><td colspan="3">Totals</td><td class="text-end">{{ fmt(summary.total_gross) }}</td><td colspan="3"></td><td class="text-end">{{ fmt(summary.total_net) }}</td><td></td></tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/driver_salary_summary/driver_salary_summary.js"></script>
