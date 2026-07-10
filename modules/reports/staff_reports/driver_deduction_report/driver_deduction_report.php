<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Deduction Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Deduction Report</strong> lists all monetary deductions applied to drivers within a date range.
          It answers: <em>"What amounts were deducted from which drivers and why?"</em> — giving payroll staff a clear record of penalty or recovery entries that reduce a driver's net pay.
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
            <tr><td><code>deductions</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Unique deduction reference number</td></tr>
            <tr><td><code>deductions</code></td><td><code>date</code></td><td>DATE</td><td>Date the deduction was recorded — used to filter by range</td></tr>
            <tr><td><code>deductions</code></td><td><code>driver_id</code></td><td>INTEGER (FK)</td><td>Links the deduction to a specific driver</td></tr>
            <tr><td><code>deductions</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Amount deducted in LKR</td></tr>
            <tr><td><code>deductions</code></td><td><code>reason</code></td><td>TEXT</td><td>Reason for the deduction — shown in report</td></tr>
            <tr><td><code>deductions</code></td><td><code>recipient_type</code></td><td>VARCHAR</td><td>Filtered to <code>driver</code> to exclude cleaner deductions</td></tr>
            <tr><td><code>drivers</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Driver reference code — displayed in report</td></tr>
            <tr><td><code>drivers</code></td><td><code>name</code></td><td>VARCHAR</td><td>Driver full name — displayed in report</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter</strong> — <code>deductions</code> where <code>recipient_type = 'driver'</code> and <code>date</code> is within the range are fetched. Optional driver filter applied if selected.</li>
          <li class="mb-1"><strong>Join drivers</strong> — A full driver list is fetched and used to look up each deduction's <code>driver_id</code> for name and ref.</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by driver name then date ascending.</li>
          <li class="mb-1"><strong>Totals</strong> — The footer shows total deduction count and total amount.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Deductions shown here reduce the driver's net salary. Check the reason column for the basis of each deduction.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-deduction-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="from" />
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="to" />
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Driver</label>
          <select class="form-select form-select-sm" v-model="driver_id" style="min-width:160px">
            <option value="">All Drivers</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }} ({{ d.ref }})</option>
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

  <div class="card" v-if="ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Driver Ref</th>
            <th>Driver Name</th>
            <th>Ref</th>
            <th>Date</th>
            <th class="text-end">Amount (LKR)</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.ded_ref">
            <td>{{ r.driver_ref }}</td>
            <td>{{ r.driver_name }}</td>
            <td>{{ r.ded_ref }}</td>
            <td>{{ r.date }}</td>
            <td class="text-end">{{ fmt(r.amount) }}</td>
            <td>{{ r.reason }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="6" class="text-center text-muted py-3">No deductions found for the selected period.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr>
            <td colspan="3">Totals</td>
            <td>{{ summary.entry_count }} entries</td>
            <td class="text-end">{{ fmt(summary.total_amount) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/driver_deduction_report/driver_deduction_report.js"></script>
