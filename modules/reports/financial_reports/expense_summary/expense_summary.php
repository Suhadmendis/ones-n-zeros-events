<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Expense Summary
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Expense Summary</strong> report provides a category-by-category breakdown of all business
          expenses for a selected date range. It answers: <em>"Where does our money go?"</em> — giving management a
          clear picture of cost distribution across fuel, maintenance, general overhead, and staff advances.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code>, <code>date</code></td><td>NUMERIC, DATE</td><td>Total fuel cost in period</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code>, <code>category</code>, <code>date</code></td><td>NUMERIC, TEXT, DATE</td><td>Maintenance costs split by sub-category</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>amount</code>, <code>date</code></td><td>NUMERIC, DATE</td><td>Overhead / admin costs</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>amount</code>, <code>date</code></td><td>NUMERIC, DATE</td><td>Staff advance payments</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All four expense tables are queried with the selected From/To date filter.</li>
          <li class="mb-1"><strong>Sum by category</strong> — Fuel is summed as one line. Vehicle expenses are split into repair, service, tyre, battery, insurance, and other sub-categories. General expenses and advances are each one line.</li>
          <li class="mb-1"><strong>Calculate percentage share</strong> — Each category's amount is divided by the grand total to give its share of overall spending.</li>
          <li class="mb-1"><strong>Donut chart</strong> — Rendered from the same category data for at-a-glance visual breakdown.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Vehicle expense sub-categories (repair, service, etc.) are determined by the <code>category</code> field on each vehicle expense record. Ensure expenses are categorised correctly when entered.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div id="exp-summary-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="from">
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="to">
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

  <!-- Donut Chart -->
  <div class="card mb-3" v-if="ran && rows.length">
    <div class="card-header py-2">
      <strong>Expense Breakdown — {{ from }} to {{ to }}</strong>
    </div>
    <div class="card-body d-flex justify-content-center">
      <div id="expSummaryChart" style="max-width:400px;width:100%;"></div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" v-if="ran">
    <div class="card-header py-2">
      <strong>Expense Summary</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Category</th>
              <th class="text-end">Amount (LKR)</th>
              <th class="text-end">% of Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.category">
              <td>{{ r.category }}</td>
              <td class="text-end">{{ fmt(r.amount) }}</td>
              <td class="text-end">{{ r.pct_of_total.toFixed(2) }}%</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="3" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td>Total</td>
              <td class="text-end">{{ fmt(total) }}</td>
              <td class="text-end">100.00%</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/financial_reports/expense_summary/expense_summary.js"></script>
