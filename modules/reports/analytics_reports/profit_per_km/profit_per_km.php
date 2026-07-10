<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Profit per KM by Vehicle
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Profit per KM</strong> report measures the financial efficiency of each vehicle by calculating
          how much profit is generated per kilometre driven. It answers: <em>"Which vehicles earn the most for every
          kilometre they travel?"</em> — helping management identify the most and least efficient assets in the fleet.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model</code></td><td>INT / TEXT</td><td>Identifies each vehicle in the result</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id</code></td><td>INT (FK)</td><td>Links trips to their vehicle</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Revenue earned per trip</td></tr>
            <tr><td><code>trips</code></td><td><code>mileage</code></td><td>NUMERIC</td><td>Kilometres driven per trip</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id, total</code></td><td>INT / NUMERIC</td><td>Fuel cost charged to each vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id, amount</code></td><td>INT / NUMERIC</td><td>Maintenance and other costs per vehicle</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — Trips, fuel expenses, and vehicle expenses are all fetched where <code>date</code> falls within the selected From–To period.</li>
          <li class="mb-1"><strong>Aggregate revenue &amp; KM per vehicle</strong> — For each trip, <code>amount</code> is summed as revenue and <code>mileage</code> is summed as total KM, grouped by <code>vehicle_id</code>.</li>
          <li class="mb-1"><strong>Aggregate costs</strong> — Fuel costs (<code>fuel_expenses.total</code>) and vehicle expenses (<code>vehicle_expenses.amount</code>) are separately summed per vehicle and added together as <code>total_cost</code>.</li>
          <li class="mb-1"><strong>Calculate profit</strong> — <code>profit = revenue − total_cost</code> for each vehicle.</li>
          <li class="mb-1"><strong>Calculate profit/KM</strong> — <code>profit_per_km = profit ÷ total_km</code> (vehicles with zero KM show "—").</li>
          <li class="mb-1"><strong>Rank &amp; sort</strong> — Vehicles are ranked 1st to last by profit/KM descending; vehicles with no KM data appear at the bottom.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> A negative profit/KM means the vehicle is costing more than it earns — investigate its fuel usage and maintenance spend.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="profit-per-km-app">
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
      <strong class="me-auto">Profit per KM by Vehicle</strong>
      <div class="d-flex align-items-center gap-2">
        <label class="mb-0 small">From</label>
        <input type="date" class="form-control form-control-sm" v-model="from" style="width:150px">
        <label class="mb-0 small">To</label>
        <input type="date" class="form-control form-control-sm" v-model="to" style="width:150px">
        <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Run
        </button>
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
    <div class="card-body">
      <div class="d-flex flex-wrap gap-1 align-items-center mb-2 pb-2 border-bottom">
        <small class="text-muted me-1">Jump to:</small>
        <button v-for="mn in months" :key="mn.month" type="button"
          class="btn btn-sm btn-outline-secondary"
          @click="selectMonth(mn.year, mn.month)">{{ mn.label }}</button>
      </div>
      <div v-if="error" class="alert alert-danger">{{ error }}</div>
      <div v-if="rows.length" class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
          <thead class="table-dark">
            <tr>
              <th>Rank</th>
              <th>Ref</th>
              <th>Plate</th>
              <th>Make</th>
              <th>Model</th>
              <th class="text-end">Revenue (LKR)</th>
              <th class="text-end">Cost (LKR)</th>
              <th class="text-end">Profit (LKR)</th>
              <th class="text-end">Total KM</th>
              <th class="text-end">Profit/KM (LKR/km)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.vehicle_id">
              <td class="fw-bold">{{ r.rank }}</td>
              <td>{{ r.ref }}</td>
              <td>{{ r.plate_number }}</td>
              <td>{{ r.make }}</td>
              <td>{{ r.model }}</td>
              <td class="text-end">{{ fmt(r.revenue) }}</td>
              <td class="text-end">{{ fmt(r.total_cost) }}</td>
              <td class="text-end">{{ fmt(r.profit) }}</td>
              <td class="text-end">{{ r.total_km.toLocaleString() }}</td>
              <td class="text-end"
                :class="r.profit_per_km === null ? '' : r.profit_per_km > 0 ? 'text-success fw-bold' : 'text-danger fw-bold'">
                {{ r.profit_per_km !== null ? r.profit_per_km.toLocaleString('en-LK', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading && ran" class="text-muted text-center py-3">No data for the selected period.</div>
    </div>
  </div>
</div>
<script src="/modules/reports/analytics_reports/profit_per_km/profit_per_km.js"></script>
