<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Highest Expense Vehicles
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Highest Expense Vehicles</strong> report ranks all fleet vehicles by their total operating cost (fuel + maintenance) for a date range.
          It answers: <em>"Which vehicles are costing the most to run?"</em> — helping management prioritise maintenance reviews or consider retiring high-cost vehicles.
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
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model, year</code></td><td>VARCHAR/INTEGER</td><td>Vehicle identity — used for display in ranked table</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id</code></td><td>INTEGER (FK)</td><td>Links fuel costs to a specific vehicle</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code></td><td>NUMERIC</td><td>Fuel fill cost — summed per vehicle as fuel cost</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>date</code></td><td>DATE</td><td>Used to filter fuel expenses within the date range</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id</code></td><td>INTEGER (FK)</td><td>Links maintenance costs to a specific vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Repair/maintenance cost — summed per vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>date</code></td><td>DATE</td><td>Used to filter maintenance expenses within the date range</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date</strong> — Both <code>fuel_expenses</code> and <code>vehicle_expenses</code> are fetched for the selected date range.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Fuel costs and maintenance costs are summed separately by <code>vehicle_id</code>.</li>
          <li class="mb-1"><strong>Combine costs</strong> — <code>total_operating_cost = fuel_cost + maintenance_cost</code> per vehicle.</li>
          <li class="mb-1"><strong>Join vehicle details</strong> — The vehicle registry is used to look up plate, make, model, and year for each vehicle ID that appears in either expense table.</li>
          <li class="mb-1"><strong>Rank and sort</strong> — Vehicles are sorted by total operating cost descending and assigned ranks (Rank 1 = most expensive, highlighted in red).</li>
          <li class="mb-1"><strong>Chart</strong> — An ApexCharts bar chart visualises the ranking visually.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Only vehicles with at least one expense entry in the period appear in this report. Vehicles with no costs are excluded.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="high-exp-veh-app">
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
      <strong class="me-auto">Highest Expense Vehicles</strong>
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
      <div v-if="rows.length">
        <div id="highExpVehChart" class="mb-3"></div>
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-dark">
              <tr>
                <th>Rank</th>
                <th>Ref</th>
                <th>Plate</th>
                <th>Make</th>
                <th>Model</th>
                <th class="text-end">Fuel Cost (LKR)</th>
                <th class="text-end">Maintenance (LKR)</th>
                <th class="text-end">Total (LKR)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in rows" :key="r.vehicle_id" :class="r.rank === 1 ? 'table-danger' : ''">
                <td class="fw-bold">{{ r.rank }}</td>
                <td>{{ r.ref }}</td>
                <td>{{ r.plate_number }}</td>
                <td>{{ r.make }}</td>
                <td>{{ r.model }}</td>
                <td class="text-end">{{ fmt(r.fuel_cost) }}</td>
                <td class="text-end">{{ fmt(r.maintenance_cost) }}</td>
                <td class="text-end fw-bold">{{ fmt(r.total_operating_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="!loading && ran" class="text-muted text-center py-3">No data for the selected period.</div>
    </div>
  </div>
</div>
<script src="/modules/reports/analytics_reports/highest_expense_vehicle/highest_expense_vehicle.js"></script>
