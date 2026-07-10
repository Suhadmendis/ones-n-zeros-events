<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Earnings
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Earnings</strong> report lists every trip a driver completed in a selected period along
          with the earnings breakdown per trip. It answers: <em>"How much did each driver earn per trip, and what is
          their total for the period?"</em> — useful for payroll verification and performance comparison.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>ref, date, driver_id, from_loc, to_loc, amount, driver_salary</code></td><td>Various</td><td>Core trip data including driver payout fields</td></tr>
            <tr><td><code>drivers</code></td><td><code>id, ref, name</code></td><td>Various</td><td>Joined to resolve driver name and reference</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter trips</strong> — Trips in the selected date range are fetched; optionally filtered to a specific driver.</li>
          <li class="mb-1"><strong>Join drivers</strong> — The driver lookup table is fetched and joined in PHP by <code>driver_id</code>.</li>
          <li class="mb-1"><strong>Total earning</strong> — <code>total_earning = driver_salary</code> per trip.</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by driver name then date ascending.</li>
          <li class="mb-1"><strong>Summary badges</strong> — Overall trip count, total driver salary, and total earning are shown above the table.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> <code>driver_salary</code> is the total driver pay per trip, set when the trip is recorded.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-earn-app" v-cloak>
  <!-- filters -->
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
          <select class="form-select form-select-sm" v-model="driverId">
            <option value="">All</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
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

  <!-- summary badges -->
  <div class="row g-2 mb-3" v-if="summary && ran">
    <div class="col-auto">
      <span class="badge bg-secondary fs-6">Trips: {{ summary.trip_count }}</span>
    </div>
    <div class="col-auto">
      <span class="badge bg-info text-dark fs-6">Driver Salary: LKR {{ fmt(summary.total_driver_salary) }}</span>
    </div>
    <div class="col-auto">
      <span class="badge bg-primary fs-6">Total Earning: LKR {{ fmt(summary.total_earning) }}</span>
    </div>
  </div>

  <!-- results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Driver Earnings Report</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Driver Ref</th>
              <th>Driver Name</th>
              <th>Trip Ref</th>
              <th>Date</th>
              <th>From</th>
              <th>To</th>
              <th class="text-end">Trip Amount (LKR)</th>
              <th class="text-end">Driver Salary (LKR)</th>
              <th class="text-end">Total (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.trip_ref">
              <td>{{ r.driver_ref }}</td>
              <td>{{ r.driver_name }}</td>
              <td>{{ r.trip_ref }}</td>
              <td>{{ r.date }}</td>
              <td>{{ r.from_loc }}</td>
              <td>{{ r.to_loc }}</td>
              <td class="text-end">{{ fmt(r.trip_amount) }}</td>
              <td class="text-end">{{ fmt(r.driver_salary) }}</td>
              <td class="text-end fw-semibold">{{ fmt(r.total_earning) }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="9" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length && summary">
            <tr>
              <td colspan="6">Total ({{ summary.trip_count }} trips)</td>
              <td></td>
              <td class="text-end">{{ fmt(summary.total_driver_salary) }}</td>
              <td class="text-end">{{ fmt(summary.total_earning) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/driver_earnings/driver_earnings.js"></script>
