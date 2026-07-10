<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Income Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Income Report</strong> summarises each driver's trip activity and gross earnings for a date range.
          It answers: <em>"How many trips did each driver complete, how much revenue did they generate, and how much did they earn?"</em> — ranking drivers by gross pay to identify top earners.
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
            <tr><td><code>trips</code></td><td><code>driver_id</code></td><td>INTEGER (FK)</td><td>Groups trips by driver</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Total trip revenue — summed per driver</td></tr>
            <tr><td><code>trips</code></td><td><code>driver_salary</code></td><td>NUMERIC</td><td>Driver's salary per trip — forms the gross earning</td></tr>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Used to filter trips within the selected date range</td></tr>
            <tr><td><code>drivers</code></td><td><code>id</code></td><td>INTEGER</td><td>Primary key — used to join with trips</td></tr>
            <tr><td><code>drivers</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Driver reference code — displayed in report</td></tr>
            <tr><td><code>drivers</code></td><td><code>name</code></td><td>VARCHAR</td><td>Driver full name — displayed in report</td></tr>
            <tr><td><code>drivers</code></td><td><code>status</code></td><td>VARCHAR</td><td>Active/inactive status — shown as badge</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Fetch trips</strong> — All trips within the date range are fetched with their revenue and driver pay columns.</li>
          <li class="mb-1"><strong>Aggregate per driver</strong> — Trips are grouped by <code>driver_id</code>, counting trips and summing <code>amount</code> and <code>driver_salary</code>.</li>
          <li class="mb-1"><strong>Gross earning</strong> — <code>gross_earning = driver_salary</code> per driver.</li>
          <li class="mb-1"><strong>Join drivers</strong> — Driver names and statuses are fetched separately and merged with the aggregated data.</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by gross earning descending so the highest earners appear first.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Revenue and Driver Earning are separate figures — revenue is what the customer paid, while driver earning is only the driver's commission portion of that revenue.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-income-app" v-cloak>
  <!-- filter card -->
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
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            Run Report
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

  <!-- results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Driver Income Report</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Name</th>
              <th>Status</th>
              <th class="text-end">Trips</th>
              <th class="text-end">Revenue (LKR)</th>
              <th class="text-end">Driver Salary (LKR)</th>
              <th class="text-end">Gross (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.ref">
              <td>{{ r.ref }}</td>
              <td>{{ r.name }}</td>
              <td>
                <span class="badge" :class="r.status === 'active' ? 'bg-success' : 'bg-secondary'">
                  {{ r.status }}
                </span>
              </td>
              <td class="text-end">{{ r.trip_count }}</td>
              <td class="text-end">{{ fmt(r.total_revenue) }}</td>
              <td class="text-end">{{ fmt(r.total_driver_salary) }}</td>
              <td class="text-end fw-semibold">{{ fmt(r.gross_earning) }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="7" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td colspan="3">Total</td>
              <td class="text-end">{{ totals.trips }}</td>
              <td class="text-end">{{ fmt(totals.revenue) }}</td>
              <td class="text-end">{{ fmt(totals.driver_salary) }}</td>
              <td class="text-end">{{ fmt(totals.gross) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/driver_income_report/driver_income_report.js"></script>
