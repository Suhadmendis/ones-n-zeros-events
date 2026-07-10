<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Income vs Expenses
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Income vs Expenses</strong> report compares total monthly income against total monthly
          expenses for each month of a selected year, showing the net position. It answers:
          <em>"In which months did we profit and in which did we lose money?"</em> — the clearest
          year-at-a-glance view of business financial health.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly income (trip revenue)</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>date, total</code></td><td>DATE, NUMERIC</td><td>Monthly fuel cost (expense)</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly vehicle maintenance (expense)</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly overhead cost (expense)</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Monthly advances paid (expense)</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by year</strong> — All five tables are queried for the selected calendar year.</li>
          <li class="mb-1"><strong>Aggregate by month</strong> — Income (trips.amount) and all expense categories are summed per calendar month.</li>
          <li class="mb-1"><strong>Calculate net</strong> — <code>net = income − total_expenses</code> per month.</li>
          <li class="mb-1"><strong>Chart rendering</strong> — A dual-line or grouped bar chart visualises income vs. expenses using ApexCharts.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Annual income, expense, and net totals are shown in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Net values in green mean the business was profitable that month; red means costs exceeded income. Advance payments are included in expenses as they represent cash outflow.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div id="inc-exp-app" v-cloak>
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
      <strong>Income vs Expenses — {{ year }}</strong>
    </div>
    <div class="card-body">
      <div id="incExpChart"></div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" v-if="ran">
    <div class="card-header py-2">
      <strong>Monthly Breakdown — {{ year }}</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Month</th>
              <th class="text-end">Income (LKR)</th>
              <th class="text-end">Expenses (LKR)</th>
              <th class="text-end">Net (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.month_key">
              <td>{{ r.month_label }}</td>
              <td class="text-end">{{ fmt(r.income) }}</td>
              <td class="text-end">{{ fmt(r.expenses) }}</td>
              <td class="text-end fw-bold" :class="r.net >= 0 ? 'text-success' : 'text-danger'">{{ fmt(r.net) }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="4" class="text-center text-muted py-3">No data for selected year.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td>Total</td>
              <td class="text-end">{{ fmt(totals.income) }}</td>
              <td class="text-end">{{ fmt(totals.expenses) }}</td>
              <td class="text-end" :class="totals.net >= 0 ? 'text-success' : 'text-danger'">{{ fmt(totals.net) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/financial_reports/income_vs_expense/income_vs_expense.js"></script>
