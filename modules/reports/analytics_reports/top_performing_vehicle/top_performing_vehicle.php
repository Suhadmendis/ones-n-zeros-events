<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Top Performing Vehicles
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Top Performing Vehicles</strong> report ranks vehicles by profit (revenue minus fuel and
          maintenance costs) over a selected date range. It answers: <em>"Which vehicles generate the most profit?"</em>
          — helping management make informed decisions about fleet investment and retirement.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model, year</code></td><td>Various</td><td>Vehicle master data</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id, amount, mileage</code></td><td>FK, NUMERIC</td><td>Revenue and mileage per vehicle</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id, total</code></td><td>FK, NUMERIC</td><td>Fuel cost per vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id, amount</code></td><td>FK, NUMERIC</td><td>Maintenance cost per vehicle</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Fetch all data</strong> — Vehicles, trips, fuel expenses, and vehicle expenses are all fetched for the selected date range.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Revenue, fuel cost, maintenance cost, and trip count are summed per <code>vehicle_id</code>.</li>
          <li class="mb-1"><strong>Calculate profit</strong> — <code>profit = revenue − fuel_cost − maintenance_cost</code>.</li>
          <li class="mb-1"><strong>Sort and rank</strong> — Results are sorted by profit descending and ranked 1, 2, 3…</li>
          <li class="mb-1"><strong>Apply limit</strong> — The Top N filter restricts output to 5, 10, or all vehicles.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> The #1 ranked vehicle row is highlighted in gold. A vehicle with high revenue but low profit may have disproportionate maintenance costs worth investigating.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div id="top-veh-app">
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
      <strong class="me-auto">Top Performing Vehicles</strong>
      <div class="d-flex align-items-center gap-2">
        <label class="mb-0 small">From</label>
        <input type="date" class="form-control form-control-sm" v-model="from" style="width:150px">
        <label class="mb-0 small">To</label>
        <input type="date" class="form-control form-control-sm" v-model="to" style="width:150px">
        <label class="mb-0 small">Top</label>
        <select class="form-select form-select-sm" v-model="limit" style="width:90px">
          <option value="5">5</option>
          <option value="10">10</option>
          <option value="all">All</option>
        </select>
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
      <div v-if="rows.length">
        <div id="topVehChart" class="mb-3"></div>
        <div class="table-responsive">
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
                <th class="text-end">Trips</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in rows" :key="r.vehicle_id" :class="r.rank === 1 ? 'table-warning' : ''">
                <td class="fw-bold">{{ r.rank }}</td>
                <td>{{ r.ref }}</td>
                <td>{{ r.plate_number }}</td>
                <td>{{ r.make }}</td>
                <td>{{ r.model }}</td>
                <td class="text-end">{{ fmt(r.revenue) }}</td>
                <td class="text-end">{{ fmt(r.total_cost) }}</td>
                <td class="text-end" :class="r.profit >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold'">{{ fmt(r.profit) }}</td>
                <td class="text-end">{{ r.trip_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="!loading && ran" class="text-muted text-center py-3">No data for the selected period.</div>
    </div>
  </div>
</div>
<script src="/modules/reports/analytics_reports/top_performing_vehicle/top_performing_vehicle.js"></script>
