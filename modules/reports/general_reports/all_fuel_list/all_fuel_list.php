<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: All Fuel List
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>All Fuel List</strong> report shows every individual fuel fill-up recorded in the system for a
          selected date range, optionally filtered to a single vehicle. It answers:
          <em>"What fuel purchases were made, when, and at what cost?"</em> — useful for auditing fuel spend and
          spotting anomalous fill-ups.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>fuel_expenses</code></td><td><code>ref, date, vehicle_id, liters, rate, total</code></td><td>Various</td><td>One row per fuel fill-up event</td></tr>
            <tr><td><code>vehicles</code></td><td><code>id, plate_number, make</code></td><td>Various</td><td>Joined to show vehicle plate number</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date and vehicle</strong> — All fuel expense records in the date range are fetched; optionally filtered to one vehicle.</li>
          <li class="mb-1"><strong>Join vehicles</strong> — The vehicle table is fetched and joined by <code>vehicle_id</code> to get plate numbers.</li>
          <li class="mb-1"><strong>Sorted by date descending</strong> — Most recent fills appear first.</li>
          <li class="mb-1"><strong>Summary totals</strong> — Entry count, total litres, and total cost are computed and shown inline.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Use the Vehicle filter to quickly audit a specific lorry's fuel history. Compare the rate column against market rates to flag unusual purchases.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="all-fuel-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="from" />
        </div>
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="to" />
        </div>
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Vehicle</label>
          <select class="form-select form-select-sm" v-model="vehicleId" style="min-width:160px">
            <option value="">All Vehicles</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} — {{ v.make }}</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            Run Report
          </button>
        </div>
        <div class="col-auto ms-auto text-muted small" v-if="summary">
          {{ summary.entry_count }} entr(ies) &nbsp;|&nbsp;
          {{ fmt(summary.total_litres) }} L &nbsp;|&nbsp;
          LKR {{ fmt(summary.total_cost) }}
        </div>
        <div class="col-auto">
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

  <!-- Results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>All Fuel List</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Ref</th>
            <th>Date</th>
            <th>Vehicle</th>
            <th class="text-end">Litres</th>
            <th class="text-end">Rate (LKR/L)</th>
            <th class="text-end">Total (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.ref + r.date">
            <td>{{ r.ref }}</td>
            <td>{{ r.date }}</td>
            <td>{{ r.plate_number }}</td>
            <td class="text-end">{{ fmt(r.liters) }}</td>
            <td class="text-end">{{ fmt(r.rate) }}</td>
            <td class="text-end">{{ fmt(r.total) }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="6" class="text-center text-muted py-3">No data for selected period.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length && summary">
          <tr>
            <td colspan="3">Total ({{ summary.entry_count }} entries)</td>
            <td class="text-end">{{ fmt(summary.total_litres) }}</td>
            <td></td>
            <td class="text-end">{{ fmt(summary.total_cost) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/all_fuel_list/all_fuel_list.js"></script>
