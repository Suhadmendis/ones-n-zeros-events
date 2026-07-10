<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Fuel Efficiency
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Fuel Efficiency</strong> report calculates the kilometres per litre (KM/L) for each vehicle over
          a selected date range. It answers: <em>"How fuel-efficient is each vehicle?"</em> — helping identify
          vehicles that consume excessive fuel and may need servicing or replacement.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model, fuel_type</code></td><td>Various</td><td>Vehicle master data</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id, mileage</code></td><td>FK, NUMERIC</td><td>Total KM driven per vehicle</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id, liters</code></td><td>FK, NUMERIC</td><td>Total litres consumed per vehicle</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Fetch data</strong> — Vehicles, trips (mileage), and fuel expenses (litres) are fetched for the selected date range.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Total KM from trips and total litres from fuel expenses are summed per <code>vehicle_id</code>.</li>
          <li class="mb-1"><strong>Calculate KM/L</strong> — <code>km_per_litre = total_km / total_litres</code>. Vehicles with no fuel or no mileage are excluded.</li>
          <li class="mb-1"><strong>Sort</strong> — Sorted by KM/L ascending (least efficient first).</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> KM/L is colour-coded — green for good efficiency, red for poor. Vehicles below 5 KM/L may warrant a maintenance check.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="fuel-eff-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1 small fw-semibold">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="filters.from">
        </div>
        <div class="col-auto">
          <label class="form-label mb-1 small fw-semibold">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="filters.to">
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

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>

  <div class="card border-0 shadow-sm" v-if="result">
    <div class="table-responsive">
      <table class="table table-sm table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Ref</th>
            <th>Plate</th>
            <th>Make</th>
            <th>Model</th>
            <th>Fuel Type</th>
            <th class="text-end">Total KM</th>
            <th class="text-end">Total Litres</th>
            <th class="text-end">KM per Litre</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="result.rows.length === 0">
            <td colspan="8" class="text-center text-muted py-3">No data for selected period</td>
          </tr>
          <tr v-for="r in result.rows" :key="r.ref">
            <td>{{ r.ref }}</td>
            <td>{{ r.plate_number }}</td>
            <td>{{ r.make }}</td>
            <td>{{ r.model }}</td>
            <td>{{ r.fuel_type }}</td>
            <td class="text-end">{{ Number(r.total_km).toLocaleString() }}</td>
            <td class="text-end">{{ Number(r.total_litres).toLocaleString() }}</td>
            <td class="text-end fw-bold" :class="kplClass(r.km_per_litre)">
              {{ r.km_per_litre !== null ? r.km_per_litre : '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="/modules/reports/fleet_reports/fuel_efficiency/fuel_efficiency.js"></script>
