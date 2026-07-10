<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Fuel Usage Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Fuel Usage Report</strong> summarises fuel consumption per vehicle for a selected
          date range — showing litres consumed, kilometres driven, fuel cost, and km-per-litre efficiency.
          It answers: <em>"How much fuel did each vehicle consume and how efficient were they?"</em> —
          the primary report for fleet fuel management.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model</code></td><td>Various</td><td>Vehicle master data for display</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id, liters, total</code></td><td>FK, NUMERIC</td><td>Fuel litres and cost per vehicle</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id, mileage</code></td><td>FK, NUMERIC</td><td>Kilometres driven per vehicle</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — Fuel expenses and trips within the selected period are fetched along with all vehicles.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Total litres, fuel cost, and total km are summed per vehicle ID.</li>
          <li class="mb-1"><strong>Calculate efficiency</strong> — <code>km_per_litre = total_km / total_litres</code>; vehicles with zero fuel records are excluded.</li>
          <li class="mb-1"><strong>Sort by km/litre ascending</strong> — Least efficient vehicles appear first for easy identification.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Total litres, km, and cost across all vehicles are shown.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> KM/Litre values are colour-coded — low values appear in red as a warning. Compare this report with the Lowest Fuel Efficiency report for a ranked view of the worst performers.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="fuel-usage-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="dateFrom" />
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="dateTo" />
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
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-hover mb-0">
        <thead class="table-dark">
          <tr><th>Ref</th><th>Plate</th><th>Make</th><th>Model</th><th class="text-end">Litres</th><th class="text-end">Total KM</th><th class="text-end">Fuel Cost (LKR)</th><th class="text-end">KM/Litre</th></tr>
        </thead>
        <tbody>
          <tr v-for="r in rows">
            <td>{{ r.ref }}</td><td>{{ r.plate_number }}</td><td>{{ r.make }}</td><td>{{ r.model }}</td>
            <td class="text-end">{{ fmt(r.total_litres) }}</td>
            <td class="text-end">{{ fmt(r.total_km) }}</td>
            <td class="text-end">{{ fmt(r.fuel_cost) }}</td>
            <td class="text-end" :class="kplClass(r.km_per_litre)">{{ r.km_per_litre ?? '—' }}</td>
          </tr>
          <tr v-if="!rows.length"><td colspan="8" class="text-center text-muted py-3">No fuel data for selected period.</td></tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr>
            <td colspan="4">Totals</td>
            <td class="text-end">{{ fmt(totals.litres) }}</td>
            <td class="text-end">{{ fmt(totals.km) }}</td>
            <td class="text-end">{{ fmt(totals.cost) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/fuel_usage_report/fuel_usage_report.js"></script>
