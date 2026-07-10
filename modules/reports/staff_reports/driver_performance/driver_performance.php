<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Driver Performance Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Driver Performance Report</strong> shows each driver's trips, mileage, revenue generated,
          driver earnings, day pay, and gross earnings over a selected date range. It answers:
          <em>"Which drivers are generating the most revenue and how much are we paying them?"</em> —
          supporting driver appraisals and payroll planning.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>drivers</code></td><td><code>id, ref, name, status</code></td><td>Various</td><td>All drivers including those with no trips</td></tr>
            <tr><td><code>trips</code></td><td><code>driver_id, amount, driver_salary, mileage</code></td><td>FK, NUMERIC</td><td>Revenue, earnings, and mileage per driver</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date and driver</strong> — Trips in the selected period are fetched; optionally filtered to one driver.</li>
          <li class="mb-1"><strong>Aggregate per driver</strong> — Trip count, total mileage, revenue, and driver salary are summed per driver ID.</li>
          <li class="mb-1"><strong>Calculate gross earning</strong> — <code>gross = driver_salary</code> per driver.</li>
          <li class="mb-1"><strong>Sort by revenue descending</strong> — Highest revenue-generating drivers appear first.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Fleet-wide totals for all columns are shown in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> All drivers appear in the report regardless of whether they had trips — those with zero trips in the period can be identified for scheduling review. Use the Driver filter to focus on one driver.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="driver-perf-app" v-cloak>
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

  <!-- results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Driver Performance Report</strong>
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
              <th class="text-center">Trips</th>
              <th class="text-end">Mileage (km)</th>
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
              <td class="text-center">{{ r.trip_count }}</td>
              <td class="text-end">{{ fmt(r.total_mileage) }}</td>
              <td class="text-end">{{ fmt(r.total_revenue) }}</td>
              <td class="text-end">{{ fmt(r.total_driver_salary) }}</td>
              <td class="text-end fw-semibold">{{ fmt(r.gross_earning) }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="8" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length && totals">
            <tr>
              <td colspan="3">Total</td>
              <td class="text-center">{{ totals.trip_count }}</td>
              <td class="text-end">{{ fmt(totals.total_mileage) }}</td>
              <td class="text-end">{{ fmt(totals.total_revenue) }}</td>
              <td class="text-end">{{ fmt(totals.total_driver_salary) }}</td>
              <td class="text-end">{{ fmt(totals.gross_earning) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/driver_performance/driver_performance.js"></script>
