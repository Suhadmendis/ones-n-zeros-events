<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Monthly Profit
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Monthly Profit</strong> report shows the net profit or loss for each month of a selected year by
          comparing trip revenue against all operating expenses. It answers: <em>"Did we make money each month, and by
          how much?"</em> — enabling management to spot unprofitable months and track margin trends over the year.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Groups revenue by month</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Monthly revenue source</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>date</code>, <code>total</code></td><td>DATE, NUMERIC</td><td>Fuel cost per month</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>date</code>, <code>amount</code></td><td>DATE, NUMERIC</td><td>Maintenance cost per month</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>date</code>, <code>amount</code></td><td>DATE, NUMERIC</td><td>Overhead cost per month</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>date</code>, <code>amount</code></td><td>DATE, NUMERIC</td><td>Staff advance costs per month</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by year</strong> — All records from each of the 5 tables are fetched for the selected year (Jan 1 – Dec 31).</li>
          <li class="mb-1"><strong>Group by month</strong> — Each record's date is truncated to <code>YYYY-MM</code> and accumulated into monthly buckets.</li>
          <li class="mb-1"><strong>Sum revenue and expenses</strong> — Revenue from <code>trips.amount</code> is summed per month. All four expense tables are summed into a combined monthly expense total.</li>
          <li class="mb-1"><strong>Calculate profit</strong> — <code>profit = revenue − expenses</code>. Profit margin is computed as <code>(profit / revenue) × 100</code>.</li>
          <li class="mb-1"><strong>Fill all 12 months</strong> — Months with no activity show zero values for complete year-wide comparison.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Rows highlighted in red indicate a loss month. The chart makes it easy to spot seasonal profit patterns at a glance.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div id="monthly-profit-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Year</label>
          <select class="form-select form-select-sm" v-model="year">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
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
    </div>
  </div>

  <!-- Chart -->
  <div class="card mb-3" v-if="ran && rows.length">
    <div class="card-header py-2">
      <strong>Monthly Profit Trend — {{ year }}</strong>
    </div>
    <div class="card-body">
      <div id="monthlyProfitChart"></div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" v-if="ran">
    <div class="card-header py-2">
      <strong>Monthly Profit — {{ year }}</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Month</th>
              <th class="text-end">Revenue (LKR)</th>
              <th class="text-end">Expenses (LKR)</th>
              <th class="text-end">Profit (LKR)</th>
              <th class="text-end">Margin %</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.month_label" :class="r.profit < 0 ? 'table-danger' : ''">
              <td>{{ r.month_label }}</td>
              <td class="text-end">{{ fmt(r.revenue) }}</td>
              <td class="text-end">{{ fmt(r.expenses) }}</td>
              <td class="text-end fw-bold" :class="r.profit >= 0 ? 'text-success' : 'text-danger'">{{ fmt(r.profit) }}</td>
              <td class="text-end">
                <span v-if="r.margin_pct !== null" :class="r.margin_pct >= 0 ? 'text-success' : 'text-danger'">
                  {{ r.margin_pct.toFixed(2) }}%
                </span>
                <span v-else class="text-muted">—</span>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="5" class="text-center text-muted py-3">No data for selected year.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td>Total</td>
              <td class="text-end">{{ fmt(totals.revenue) }}</td>
              <td class="text-end">{{ fmt(totals.expenses) }}</td>
              <td class="text-end" :class="totals.profit >= 0 ? 'text-success' : 'text-danger'">{{ fmt(totals.profit) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/financial_reports/monthly_profit/monthly_profit.js"></script>
