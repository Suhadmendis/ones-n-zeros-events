<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Most Expensive Vehicles
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Most Expensive Vehicles</strong> report ranks fleet vehicles by their combined fuel and maintenance costs for a date range.
          It answers: <em>"Which vehicles cost the most to operate, and what is the breakdown between fuel and maintenance?"</em> — helping management focus cost-reduction efforts on the highest-cost assets.
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
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model, year</code></td><td>VARCHAR/INTEGER</td><td>Vehicle identity — displayed in ranked results</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id</code></td><td>INTEGER (FK)</td><td>Links fuel costs to a vehicle</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code></td><td>NUMERIC</td><td>Fuel cost per fill — summed as fuel cost per vehicle</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>date</code></td><td>DATE</td><td>Filters fuel expenses within the date range</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id</code></td><td>INTEGER (FK)</td><td>Links maintenance costs to a vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Maintenance cost — summed as maintenance cost per vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>date</code></td><td>DATE</td><td>Filters maintenance expenses within the date range</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date</strong> — Both <code>fuel_expenses</code> and <code>vehicle_expenses</code> are fetched for the selected date range.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Fuel costs and maintenance costs are summed separately by <code>vehicle_id</code>.</li>
          <li class="mb-1"><strong>Total cost</strong> — <code>total_cost = fuel_cost + maintenance_cost</code> per vehicle.</li>
          <li class="mb-1"><strong>Join vehicle details</strong> — The vehicle registry is used to look up plate, make, model, and year for each vehicle ID.</li>
          <li class="mb-1"><strong>Rank and sort</strong> — Vehicles are sorted by total cost descending and ranked (Rank 1 = most expensive, highlighted in red).</li>
          <li class="mb-1"><strong>Totals row</strong> — The footer shows fleet-wide sums of fuel, maintenance, and total cost.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Compare this report against Vehicle Income Report to assess whether high-cost vehicles are also high-revenue earners, or are net cost burdens.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="most-exp-veh-app" v-cloak>
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

  <!-- results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Most Expensive Vehicles</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Rank</th>
              <th>Ref</th>
              <th>Plate</th>
              <th>Make</th>
              <th>Model</th>
              <th>Year</th>
              <th class="text-end">Fuel Cost (LKR)</th>
              <th class="text-end">Maintenance (LKR)</th>
              <th class="text-end">Total Cost (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.ref" :class="r.rank === 1 ? 'table-danger' : ''">
              <td class="text-center fw-bold">{{ r.rank }}</td>
              <td>{{ r.ref }}</td>
              <td>{{ r.plate_number }}</td>
              <td>{{ r.make }}</td>
              <td>{{ r.model }}</td>
              <td>{{ r.year }}</td>
              <td class="text-end">{{ fmt(r.fuel_cost) }}</td>
              <td class="text-end">{{ fmt(r.maintenance_cost) }}</td>
              <td class="text-end fw-semibold">{{ fmt(r.total_cost) }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="9" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td colspan="6">Total</td>
              <td class="text-end">{{ fmt(totals.fuel_cost) }}</td>
              <td class="text-end">{{ fmt(totals.maintenance_cost) }}</td>
              <td class="text-end">{{ fmt(totals.total_cost) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/fleet_reports/most_expensive_vehicle/most_expensive_vehicle.js"></script>
