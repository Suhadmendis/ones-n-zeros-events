<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Daily Business Snapshot
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Daily Business Snapshot</strong> gives a complete operational picture for any single day —
          trips, revenue, fuel spend, expenses, and net position — in one view. It answers:
          <em>"How did the business perform today?"</em> — the go-to morning and end-of-day check for operations managers.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>id, ref, date, amount, driver_salary, cleaner_salary, from_loc, to_loc, run_no, vehicle_id, driver_id</code></td><td>Various</td><td>All trip activity for the day</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code></td><td>NUMERIC</td><td>Total fuel spend for the day</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Vehicle maintenance for the day</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Overhead for the day</td></tr>
            <tr><td><code>vehicles</code></td><td><code>id, plate_number, ref</code></td><td>Various</td><td>Joined to show vehicle plate in trip detail</td></tr>
            <tr><td><code>drivers</code></td><td><code>id, name</code></td><td>Various</td><td>Joined to show driver name in trip detail</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date</strong> — All five tables are queried for the exact selected date (date = YYYY-MM-DD).</li>
          <li class="mb-1"><strong>KPI calculations</strong> — Revenue = sum of trip amounts; net position = revenue − fuel − vehicle expenses − general expenses.</li>
          <li class="mb-1"><strong>Trip detail</strong> — Each trip is enriched with vehicle plate and driver name via in-PHP joins.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> The default date is today. Change the date picker to review any past day. Net Position does not include driver/cleaner payouts — those are tracked separately in advance payments.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="daily-snapshot-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1 small fw-semibold">Date</label>
          <input type="date" class="form-control form-control-sm" v-model="filters.date">
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

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>

  <template v-if="data">
    <!-- KPI Cards -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small mb-1">Trips Today</div>
            <div class="fs-3 fw-bold">{{ data.trip_count }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small mb-1">Revenue</div>
            <div class="fs-5 fw-bold text-success">LKR {{ fmt(data.revenue) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small mb-1">Fuel Spend</div>
            <div class="fs-5 fw-bold text-warning">LKR {{ fmt(data.fuel_cost) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small mb-1">Expenses</div>
            <div class="fs-5 fw-bold text-danger">LKR {{ fmt(parseFloat(data.vehicle_exp||0)+parseFloat(data.general_exp||0)) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="text-muted small mb-1">Net Position</div>
            <div class="fs-5 fw-bold" :class="data.net_position >= 0 ? 'text-success' : 'text-danger'">
              LKR {{ fmt(data.net_position) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Trip List Table -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Trip Details</div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Ref</th>
              <th>Vehicle</th>
              <th>Driver</th>
              <th>From</th>
              <th>To</th>
              <th>Run No</th>
              <th class="text-end">Amount</th>
              <th class="text-end">Driver Salary</th>
              <th class="text-end">Cleaner Salary</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="data.trip_list.length === 0">
              <td colspan="9" class="text-center text-muted py-3">No trips for this date</td>
            </tr>
            <tr v-for="t in data.trip_list" :key="t.ref">
              <td>{{ t.ref }}</td>
              <td>{{ t.plate }}</td>
              <td>{{ t.driver }}</td>
              <td>{{ t.from_loc }}</td>
              <td>{{ t.to_loc }}</td>
              <td>{{ t.run_no }}</td>
              <td class="text-end">{{ fmt(t.amount) }}</td>
              <td class="text-end">{{ fmt(t.driver_salary) }}</td>
              <td class="text-end">{{ fmt(t.cleaner_salary) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>
</div>
<script src="/modules/reports/executive_reports/daily_business_snapshot/daily_business_snapshot.js"></script>
