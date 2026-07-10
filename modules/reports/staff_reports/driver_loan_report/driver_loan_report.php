<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Loan Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Loan Report</strong> lists all loans issued to drivers, showing principal, recovered amount, outstanding balance, and status.
          It answers: <em>"Which drivers have active loans and what is still outstanding?"</em> — giving management a full picture of staff debt obligations.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr>
              <th>Table</th>
              <th>Column</th>
              <th>Type</th>
              <th>Role in this report</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>loans</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Unique loan reference number</td></tr>
            <tr><td><code>loans</code></td><td><code>date</code></td><td>DATE</td><td>Date the loan was issued</td></tr>
            <tr><td><code>loans</code></td><td><code>driver_id</code></td><td>INTEGER (FK)</td><td>Links the loan to a specific driver</td></tr>
            <tr><td><code>loans</code></td><td><code>principal_amount</code></td><td>NUMERIC</td><td>Original loan amount in LKR</td></tr>
            <tr><td><code>loans</code></td><td><code>recovered_amount</code></td><td>NUMERIC</td><td>Amount repaid so far — used to compute remaining balance</td></tr>
            <tr><td><code>loans</code></td><td><code>status</code></td><td>VARCHAR</td><td>Loan status: <code>active</code> or <code>settled</code></td></tr>
            <tr><td><code>loans</code></td><td><code>recipient_type</code></td><td>VARCHAR</td><td>Filtered to <code>driver</code> to exclude cleaner loans</td></tr>
            <tr><td><code>drivers</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Driver reference code — displayed in report</td></tr>
            <tr><td><code>drivers</code></td><td><code>name</code></td><td>VARCHAR</td><td>Driver full name — displayed in report</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter</strong> — <code>loans</code> where <code>recipient_type = 'driver'</code> are fetched. Optional driver and status filters are applied if selected.</li>
          <li class="mb-1"><strong>Join drivers</strong> — A full driver list is fetched and used to look up each loan's <code>driver_id</code> for display name and ref.</li>
          <li class="mb-1"><strong>Calculate balance</strong> — <code>remaining_balance = principal_amount − recovered_amount</code> is computed per row.</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by driver name then date ascending.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Filter by status "Active" to see only outstanding loans. Remaining balance in red means the driver still owes money.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-loan-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Driver</label>
          <select class="form-select form-select-sm" v-model="driver_id" style="min-width:160px">
            <option value="">All Drivers</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }} ({{ d.ref }})</option>
          </select>
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Status</label>
          <select class="form-select form-select-sm" v-model="status">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="settled">Settled</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Run Report
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

  <div class="card" v-if="ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Driver Ref</th>
            <th>Driver Name</th>
            <th>Loan Ref</th>
            <th>Date</th>
            <th class="text-end">Principal (LKR)</th>
            <th class="text-end">Recovered (LKR)</th>
            <th class="text-end">Remaining (LKR)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.loan_ref">
            <td>{{ r.driver_ref }}</td>
            <td>{{ r.driver_name }}</td>
            <td>{{ r.loan_ref }}</td>
            <td>{{ r.date }}</td>
            <td class="text-end">{{ fmt(r.principal_amount) }}</td>
            <td class="text-end">{{ fmt(r.recovered_amount) }}</td>
            <td class="text-end" :class="r.remaining_balance > 0 ? 'text-danger fw-semibold' : ''">{{ fmt(r.remaining_balance) }}</td>
            <td>
              <span :class="'badge bg-' + (r.status === 'settled' ? 'success' : 'warning text-dark')">{{ r.status }}</span>
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="8" class="text-center text-muted py-3">No loans found for the selected filters.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/driver_loan_report/driver_loan_report.js"></script>
