<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Mileage Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Mileage Report</strong> summarises total distance driven per vehicle for a date range, derived from trip odometer readings.
          It answers: <em>"How many kilometres did each vehicle cover and across how many trips?"</em> — useful for maintenance scheduling and fuel efficiency benchmarking.
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
            <tr><td><code>trips</code></td><td><code>vehicle_id</code></td><td>INTEGER (FK)</td><td>Groups trips by vehicle</td></tr>
            <tr><td><code>trips</code></td><td><code>mileage</code></td><td>NUMERIC</td><td>Distance covered on this trip in km — summed per vehicle</td></tr>
            <tr><td><code>trips</code></td><td><code>opening_km</code></td><td>NUMERIC</td><td>Odometer reading at trip start — minimum value shown as opening KM</td></tr>
            <tr><td><code>trips</code></td><td><code>closing_km</code></td><td>NUMERIC</td><td>Odometer reading at trip end — maximum value shown as closing KM</td></tr>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Used to filter trips within the selected date range</td></tr>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model</code></td><td>VARCHAR/INTEGER</td><td>Vehicle identity — displayed in the report</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter trips</strong> — All trips in the selected date range are fetched. If a specific vehicle is selected, only that vehicle's trips are returned.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Trip count is counted; mileage values are summed; the minimum <code>opening_km</code> and maximum <code>closing_km</code> across all trips are recorded.</li>
          <li class="mb-1"><strong>Exclude zero-trip vehicles</strong> — When "All Vehicles" is selected, vehicles with no trips in the period are excluded from results.</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by total mileage descending.</li>
          <li class="mb-1"><strong>Totals row</strong> — The footer sums total mileage and trip count across all listed vehicles.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Opening KM shows the earliest odometer reading recorded for a vehicle in the period; Closing KM shows the latest — they are not necessarily from consecutive trips.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="mileage-app" v-cloak>
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
          <label class="form-label mb-1 small fw-semibold">Vehicle</label>
          <select class="form-select form-select-sm" v-model="filters.vehicle_id">
            <option value="all">All Vehicles</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} — {{ v.ref }}</option>
          </select>
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
            <th class="text-end">Opening KM</th>
            <th class="text-end">Closing KM</th>
            <th class="text-end">Total Mileage (km)</th>
            <th class="text-end">Trips</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="result.rows.length === 0">
            <td colspan="8" class="text-center text-muted py-3">No mileage data for selected period</td>
          </tr>
          <tr v-for="r in result.rows" :key="r.ref">
            <td>{{ r.ref }}</td>
            <td>{{ r.plate_number }}</td>
            <td>{{ r.make }}</td>
            <td>{{ r.model }}</td>
            <td class="text-end">{{ r.opening_km ? Number(r.opening_km).toLocaleString() : '—' }}</td>
            <td class="text-end">{{ r.closing_km ? Number(r.closing_km).toLocaleString() : '—' }}</td>
            <td class="text-end fw-semibold">{{ Number(r.total_mileage).toLocaleString() }}</td>
            <td class="text-end">{{ r.trip_count }}</td>
          </tr>
        </tbody>
        <tfoot class="table-dark fw-bold" v-if="result.totals">
          <tr>
            <td colspan="6">TOTALS</td>
            <td class="text-end">{{ Number(result.totals.total_mileage).toLocaleString() }}</td>
            <td class="text-end">{{ result.totals.trip_count }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<script src="/modules/reports/fleet_reports/mileage_report/mileage_report.js"></script>
