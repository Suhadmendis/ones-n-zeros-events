<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Vehicle Expense Analysis
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Vehicle Expense Analysis</strong> report breaks down maintenance costs per vehicle into six
          sub-categories: repair, service, tyre, battery, insurance, and other. It answers:
          <em>"What type of maintenance does each vehicle require most?"</em> — enabling targeted cost control.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number</code></td><td>Various</td><td>Vehicle master data for display</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id, amount, category</code></td><td>FK, NUMERIC, TEXT</td><td>Individual expense records with category tags</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date and vehicle</strong> — Vehicle expenses in the selected date range are fetched, optionally filtered by vehicle.</li>
          <li class="mb-1"><strong>Pivot by category</strong> — Each expense is accumulated into its category bucket (repair, service, tyre, battery, insurance, other) per vehicle.</li>
          <li class="mb-1"><strong>Sum total</strong> — All category amounts are summed to give a per-vehicle total cost.</li>
          <li class="mb-1"><strong>Sort</strong> — Sorted by total descending (highest-cost vehicles first).</li>
          <li class="mb-1"><strong>Column totals</strong> — A footer row shows the grand total for each expense category across all vehicles.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Cells showing "—" indicate zero spend in that category for the selected period, not missing data. Use the Vehicle filter to drill into a specific vehicle.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="veh-exp-analysis-app" v-cloak>
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
            <th class="text-end">Repair</th>
            <th class="text-end">Service</th>
            <th class="text-end">Tyre</th>
            <th class="text-end">Battery</th>
            <th class="text-end">Insurance</th>
            <th class="text-end">Other</th>
            <th class="text-end">Total (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="result.rows.length === 0">
            <td colspan="9" class="text-center text-muted py-3">No expense data for selected period</td>
          </tr>
          <tr v-for="r in result.rows" :key="r.ref">
            <td>{{ r.ref }}</td>
            <td>{{ r.plate_number }}</td>
            <td class="text-end">{{ r.repair > 0 ? fmt(r.repair) : '—' }}</td>
            <td class="text-end">{{ r.service > 0 ? fmt(r.service) : '—' }}</td>
            <td class="text-end">{{ r.tyre > 0 ? fmt(r.tyre) : '—' }}</td>
            <td class="text-end">{{ r.battery > 0 ? fmt(r.battery) : '—' }}</td>
            <td class="text-end">{{ r.insurance > 0 ? fmt(r.insurance) : '—' }}</td>
            <td class="text-end">{{ r.other > 0 ? fmt(r.other) : '—' }}</td>
            <td class="text-end fw-semibold">{{ fmt(r.total) }}</td>
          </tr>
        </tbody>
        <tfoot class="table-dark fw-bold" v-if="result.totals">
          <tr>
            <td colspan="2">TOTALS</td>
            <td class="text-end">{{ fmt(result.totals.repair) }}</td>
            <td class="text-end">{{ fmt(result.totals.service) }}</td>
            <td class="text-end">{{ fmt(result.totals.tyre) }}</td>
            <td class="text-end">{{ fmt(result.totals.battery) }}</td>
            <td class="text-end">{{ fmt(result.totals.insurance) }}</td>
            <td class="text-end">{{ fmt(result.totals.other) }}</td>
            <td class="text-end">{{ fmt(result.totals.total) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<script src="/modules/reports/fleet_reports/vehicle_expense_analysis/vehicle_expense_analysis.js"></script>
