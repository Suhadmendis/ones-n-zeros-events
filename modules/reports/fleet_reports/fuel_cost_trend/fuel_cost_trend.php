<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Fuel Cost Trend
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Fuel Cost Trend</strong> report shows month-by-month fuel expenditure and consumption for a selected year, optionally filtered by vehicle.
          It answers: <em>"How is our fuel spending trending across the year and are costs rising or falling?"</em> — useful for budgeting and identifying fuel-heavy months.
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
            <tr><td><code>fuel_expenses</code></td><td><code>date</code></td><td>DATE</td><td>Fill date — used to filter by year and group by month</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>liters</code></td><td>NUMERIC</td><td>Litres of fuel filled — summed per month</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>rate</code></td><td>NUMERIC</td><td>Price per litre — averaged per month</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code></td><td>NUMERIC</td><td>Total cost of the fill — summed per month</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>vehicle_id</code></td><td>INTEGER (FK)</td><td>Optionally filtered to a specific vehicle</td></tr>
            <tr><td><code>vehicles</code></td><td><code>id</code></td><td>INTEGER</td><td>Primary key — used in vehicle dropdown</td></tr>
            <tr><td><code>vehicles</code></td><td><code>plate_number</code></td><td>VARCHAR</td><td>Shown in vehicle dropdown filter</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by year and vehicle</strong> — All <code>fuel_expenses</code> for the selected year (Jan 1 – Dec 31) are fetched. If a vehicle is selected, an additional <code>vehicle_id</code> filter is applied.</li>
          <li class="mb-1"><strong>Group by month</strong> — Each fill's date is truncated to <code>YYYY-MM</code> and accumulated into 12 monthly buckets.</li>
          <li class="mb-1"><strong>Aggregate</strong> — Per month: litres are summed, total cost is summed, and average rate is calculated from all fills that had a non-zero rate.</li>
          <li class="mb-1"><strong>Fill all 12 months</strong> — Months with no fuel activity appear with zero values, ensuring the full year is always displayed.</li>
          <li class="mb-1"><strong>Chart</strong> — An ApexCharts bar chart visualises monthly cost alongside litres consumed.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Select "All Vehicles" to see total fleet fuel spend, or pick a specific vehicle to analyse individual consumption patterns.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="fuel-trend-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1 small fw-semibold">Year</label>
          <select class="form-select form-select-sm" v-model="filters.year">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
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
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>

  <template v-if="rows.length">
    <!-- Chart -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Monthly Fuel Cost</div>
      <div class="card-body">
        <div id="fuelTrendChart"></div>
      </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Month</th>
              <th class="text-end">Total Litres</th>
              <th class="text-end">Avg Rate (LKR/L)</th>
              <th class="text-end">Total Cost (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.month_key">
              <td>{{ r.month_label }}</td>
              <td class="text-end">{{ Number(r.total_litres).toLocaleString() }}</td>
              <td class="text-end">{{ r.avg_rate > 0 ? fmt(r.avg_rate) : '—' }}</td>
              <td class="text-end">{{ fmt(r.total_cost) }}</td>
            </tr>
          </tbody>
          <tfoot class="table-dark fw-bold">
            <tr>
              <td>TOTALS</td>
              <td class="text-end">{{ Number(totalLitres).toLocaleString() }}</td>
              <td></td>
              <td class="text-end">{{ fmt(totalCost) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </template>
</div>
<script src="/modules/reports/fleet_reports/fuel_cost_trend/fuel_cost_trend.js"></script>
