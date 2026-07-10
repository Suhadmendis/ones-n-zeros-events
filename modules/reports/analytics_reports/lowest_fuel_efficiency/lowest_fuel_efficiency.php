<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Lowest Fuel Efficiency Vehicles
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Lowest Fuel Efficiency Vehicles</strong> report ranks vehicles from worst to best fuel
          economy (km per litre) over a selected date range. It answers:
          <em>"Which vehicles are burning the most fuel per kilometre?"</em> — enabling proactive maintenance
          or fleet retirement decisions for underperforming vehicles.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model, year</code></td><td>Various</td><td>Vehicle master data for display</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id, mileage</code></td><td>FK, NUMERIC</td><td>Kilometres driven per vehicle in the period</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id, liters</code></td><td>FK, NUMERIC</td><td>Fuel consumed per vehicle in the period</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — Trips and fuel expenses within the selected period are fetched along with all vehicles.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Total mileage (km) and total litres are summed per vehicle ID.</li>
          <li class="mb-1"><strong>Calculate km/litre</strong> — Vehicles with zero mileage or zero fuel are excluded; the rest get <code>km_per_litre = total_km / total_litres</code>.</li>
          <li class="mb-1"><strong>Sort ascending</strong> — Vehicles are ranked worst to best (lowest km/litre first), so the most inefficient vehicle is always #1.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Rows highlighted in red indicate vehicles below 5 km/litre — these warrant immediate inspection. Yellow rows (5–8 km/litre) are borderline and should be monitored.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="low-fuel-eff-app">
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
      <strong class="me-auto">Lowest Fuel Efficiency Vehicles</strong>
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
              <th>Year</th>
              <th class="text-end">Total KM</th>
              <th class="text-end">Total Litres</th>
              <th class="text-end">KM/Litre</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.vehicle_id" :class="r.km_per_litre < 5 ? 'table-danger' : ''">
              <td class="fw-bold">{{ r.rank }}</td>
              <td>{{ r.ref }}</td>
              <td>{{ r.plate_number }}</td>
              <td>{{ r.make }}</td>
              <td>{{ r.model }}</td>
              <td>{{ r.year }}</td>
              <td class="text-end">{{ r.total_km.toLocaleString() }}</td>
              <td class="text-end">{{ r.total_litres.toLocaleString() }}</td>
              <td class="text-end"
                :class="r.km_per_litre < 5 ? 'text-danger fw-bold' : r.km_per_litre < 8 ? 'text-warning fw-bold' : 'text-success fw-bold'">
                {{ r.km_per_litre }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="!loading && ran" class="text-muted text-center py-3">No data for the selected period.</div>
    </div>
  </div>
</div>
<script src="/modules/reports/analytics_reports/lowest_fuel_efficiency/lowest_fuel_efficiency.js"></script>
