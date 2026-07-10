<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Vehicle Profitability Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Vehicle Profitability Report</strong> shows revenue, fuel cost, vehicle maintenance cost,
          and net profit for each vehicle over a selected date range. It answers:
          <em>"Which vehicles are actually making money and which are a drain on resources?"</em> — enabling
          data-driven fleet management decisions.
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
            <tr><td><code>trips</code></td><td><code>vehicle_id, amount</code></td><td>FK, NUMERIC</td><td>Revenue generated per vehicle</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id, total</code></td><td>FK, NUMERIC</td><td>Fuel cost per vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id, amount</code></td><td>FK, NUMERIC</td><td>Maintenance cost per vehicle</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — Trips, fuel expenses, and vehicle expenses are all fetched for the selected period.</li>
          <li class="mb-1"><strong>Aggregate per vehicle</strong> — Revenue, fuel cost, and maintenance cost are summed per vehicle ID.</li>
          <li class="mb-1"><strong>Calculate profit</strong> — <code>profit = revenue − fuel_cost − vehicle_expense</code> per vehicle.</li>
          <li class="mb-1"><strong>Sort by profit descending</strong> — Most profitable vehicles appear at the top.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Grand totals for revenue, fuel cost, expense, and profit are shown in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Profit cells highlighted in red indicate vehicles costing more than they earn in the period. Driver and cleaner payouts are not included here — see the Business Summary Dashboard for the full picture.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="vehicle-profit-app" v-cloak>
  <!-- filter card -->
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
      <strong>Vehicle Profitability Report</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Plate</th>
              <th>Make</th>
              <th>Model</th>
              <th class="text-end">Revenue (LKR)</th>
              <th class="text-end">Fuel Cost (LKR)</th>
              <th class="text-end">Veh. Expense (LKR)</th>
              <th class="text-end">Profit (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.ref">
              <td>{{ r.ref }}</td>
              <td>{{ r.plate_number }}</td>
              <td>{{ r.make }}</td>
              <td>{{ r.model }}</td>
              <td class="text-end">{{ fmt(r.revenue) }}</td>
              <td class="text-end">{{ fmt(r.fuel_cost) }}</td>
              <td class="text-end">{{ fmt(r.veh_expense) }}</td>
              <td class="text-end fw-semibold" :class="parseFloat(r.profit) >= 0 ? 'text-success' : 'text-danger'">
                {{ fmt(r.profit) }}
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="8" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td colspan="4">Total</td>
              <td class="text-end">{{ fmt(totals.revenue) }}</td>
              <td class="text-end">{{ fmt(totals.fuel_cost) }}</td>
              <td class="text-end">{{ fmt(totals.veh_expense) }}</td>
              <td class="text-end" :class="totals.profit >= 0 ? 'text-success' : 'text-danger'">
                {{ fmt(totals.profit) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/vehicle_profitability_report/vehicle_profitability_report.js"></script>
