<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Trip Revenue Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Trip Revenue Report</strong> provides a full line-by-line listing of every trip's revenue and
          payout breakdown within a date range, with optional filtering by vehicle or driver. It answers:
          <em>"What exactly was charged and paid out on each trip?"</em> — giving finance and operations a complete
          transaction-level view of income and expenditure per journey.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>ref, date, from_loc, to_loc, item_name, run_no</code></td><td>TEXT / DATE</td><td>Trip identity, route, cargo type, and run number</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Total revenue for the trip (LKR)</td></tr>
            <tr><td><code>trips</code></td><td><code>driver_salary</code></td><td>NUMERIC</td><td>Driver's payment for the trip</td></tr>
            <tr><td><code>trips</code></td><td><code>cleaner_salary</code></td><td>NUMERIC</td><td>Cleaner's payment for the trip</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id, driver_id</code></td><td>INT (FK)</td><td>Used to filter by vehicle/driver and look up names</td></tr>
            <tr><td><code>vehicles</code></td><td><code>id, plate_number</code></td><td>INT / TEXT</td><td>Provides the vehicle plate number for each row</td></tr>
            <tr><td><code>drivers</code></td><td><code>id, name</code></td><td>INT / TEXT</td><td>Provides the driver name for each row</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All trips where <code>date</code> falls within the selected From–To period are fetched, ordered by date ascending.</li>
          <li class="mb-1"><strong>Optional vehicle/driver filter</strong> — If a vehicle or driver is selected, an additional filter (<code>vehicle_id=eq.X</code> or <code>driver_id=eq.X</code>) is appended to the query.</li>
          <li class="mb-1"><strong>Look up names</strong> — Vehicle plate numbers and driver names are fetched separately and matched to each trip row by their ID.</li>
          <li class="mb-1"><strong>Build detail rows</strong> — Each trip produces one row with ref, date, vehicle, driver, route, item, run number, and all three financial figures.</li>
          <li class="mb-1"><strong>Calculate summary</strong> — Revenue, driver salary, and cleaner salary are individually summed for the footer and the summary badge strip above the table.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Use the Vehicle and Driver filters together to audit a specific driver–vehicle pairing over a period.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="trip-revenue-app" v-cloak>
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
          <label class="form-label mb-1">Vehicle</label>
          <select class="form-select form-select-sm" v-model="vehicleId">
            <option value="">All</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} ({{ v.ref }})</option>
          </select>
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
      <span class="badge bg-primary fs-6">Revenue: LKR {{ fmt(summary.total_amount) }}</span>
    </div>
    <div class="col-auto">
      <span class="badge bg-info text-dark fs-6">Driver: LKR {{ fmt(summary.total_driver_salary) }}</span>
    </div>
    <div class="col-auto">
      <span class="badge bg-warning text-dark fs-6">Cleaner: LKR {{ fmt(summary.total_cleaner_salary) }}</span>
    </div>
  </div>

  <!-- results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Trip Revenue Report</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Date</th>
              <th>Vehicle</th>
              <th>Driver</th>
              <th>From</th>
              <th>To</th>
              <th>Item</th>
              <th>Run No</th>
              <th class="text-end">Amount (LKR)</th>
              <th class="text-end">Driver Salary (LKR)</th>
              <th class="text-end">Cleaner Salary (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.ref">
              <td>{{ r.ref }}</td>
              <td>{{ r.date }}</td>
              <td>{{ r.plate_number }}</td>
              <td>{{ r.driver_name }}</td>
              <td>{{ r.from_loc }}</td>
              <td>{{ r.to_loc }}</td>
              <td>{{ r.item_name }}</td>
              <td>{{ r.run_no }}</td>
              <td class="text-end">{{ fmt(r.amount) }}</td>
              <td class="text-end">{{ fmt(r.driver_salary) }}</td>
              <td class="text-end">{{ fmt(r.cleaner_salary) }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="11" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length && summary">
            <tr>
              <td colspan="8">Total ({{ summary.trip_count }} trips)</td>
              <td class="text-end">{{ fmt(summary.total_amount) }}</td>
              <td class="text-end">{{ fmt(summary.total_driver_salary) }}</td>
              <td class="text-end">{{ fmt(summary.total_cleaner_salary) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/operations_reports/trip_revenue/trip_revenue.js"></script>
