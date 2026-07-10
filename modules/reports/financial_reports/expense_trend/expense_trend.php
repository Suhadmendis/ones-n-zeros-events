<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Expense Trend
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Expense Trend</strong> report shows monthly expenses broken down by category (fuel,
          vehicle, general, advances) across all 12 months of a selected year, with month-over-month
          percentage change. It answers:
          <em>"How are our costs trending and which categories are driving expense growth?"</em> — enabling
          budget planning and cost control.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>fuel_expenses</code></td><td><code>date, total</code></td><td>DATE, NUMERIC</td><td>Monthly fuel cost aggregation</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly vehicle maintenance aggregation</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly overhead expense aggregation</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly advance payments aggregation</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by year</strong> — All four expense tables are queried for the selected calendar year.</li>
          <li class="mb-1"><strong>Aggregate by month</strong> — Each category is summed per calendar month, ensuring all 12 months appear even with zero data.</li>
          <li class="mb-1"><strong>MoM change</strong> — Month-over-month percentage change is computed on the combined total expense.</li>
          <li class="mb-1"><strong>Stacked chart</strong> — A stacked bar chart visualises the breakdown of expense categories using ApexCharts.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Annual totals for each category and overall are shown in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Red arrows (▲) in the MoM column mean costs increased vs. the prior month; green arrows (▼) mean costs decreased. Use alongside the Revenue Trend to compare income vs. expense growth.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div id="exp-trend-app" v-cloak>
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

  <!-- Stacked Bar Chart -->
  <div class="card mb-3" v-if="ran && rows.length">
    <div class="card-header py-2">
      <strong>Expense Trend — {{ year }}</strong>
    </div>
    <div class="card-body">
      <div id="expTrendChart"></div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" v-if="ran">
    <div class="card-header py-2">
      <strong>Monthly Expense Breakdown — {{ year }}</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Month</th>
              <th class="text-end">Fuel (LKR)</th>
              <th class="text-end">Vehicle (LKR)</th>
              <th class="text-end">General (LKR)</th>
              <th class="text-end">Advances (LKR)</th>
              <th class="text-end">Total (LKR)</th>
              <th class="text-end">MoM %</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.month_label">
              <td>{{ r.month_label }}</td>
              <td class="text-end">{{ fmt(r.fuel) }}</td>
              <td class="text-end">{{ fmt(r.vehicle_exp) }}</td>
              <td class="text-end">{{ fmt(r.general_exp) }}</td>
              <td class="text-end">{{ fmt(r.advances) }}</td>
              <td class="text-end fw-bold">{{ fmt(r.total) }}</td>
              <td class="text-end">
                <span v-if="r.mom_change === null" class="text-muted">—</span>
                <span v-else-if="r.mom_change >= 0" class="text-danger">▲ {{ r.mom_change.toFixed(2) }}%</span>
                <span v-else class="text-success">▼ {{ Math.abs(r.mom_change).toFixed(2) }}%</span>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="7" class="text-center text-muted py-3">No data for selected year.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td>Total</td>
              <td class="text-end">{{ fmt(totals.fuel) }}</td>
              <td class="text-end">{{ fmt(totals.vehicle_exp) }}</td>
              <td class="text-end">{{ fmt(totals.general_exp) }}</td>
              <td class="text-end">{{ fmt(totals.advances) }}</td>
              <td class="text-end">{{ fmt(totals.total) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/financial_reports/expense_trend/expense_trend.js"></script>
