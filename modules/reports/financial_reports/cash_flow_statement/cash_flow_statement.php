<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Cash Flow Statement
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Cash Flow Statement</strong> presents a chronological ledger of every inflow (trip revenue) and
          outflow (fuel, vehicle expenses, general expenses, advances) with a running balance. It answers:
          <em>"What is our real-time cash position after every transaction?"</em> — the most granular financial view available.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>date, ref, amount</code></td><td>DATE, TEXT, NUMERIC</td><td>Inflow — trip revenue</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>date, ref, total</code></td><td>DATE, TEXT, NUMERIC</td><td>Outflow — fuel cost</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>date, ref, amount</code></td><td>DATE, TEXT, NUMERIC</td><td>Outflow — maintenance cost</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>date, ref, amount</code></td><td>DATE, TEXT, NUMERIC</td><td>Outflow — overhead cost</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>date, ref, amount</code></td><td>DATE, TEXT, NUMERIC</td><td>Outflow — staff advances</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Fetch all transactions</strong> — All 5 tables are queried for the selected date range. Each record is classified as inflow or outflow.</li>
          <li class="mb-1"><strong>Merge and sort</strong> — All records are combined into a single array and sorted by date ascending.</li>
          <li class="mb-1"><strong>Compute running balance</strong> — Starting from 0, each row adds or subtracts its amount to produce a cumulative running balance.</li>
          <li class="mb-1"><strong>KPI cards</strong> — Total inflow, total outflow, and net balance are displayed as summary cards at the top.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Green rows are inflows (revenue); red rows are outflows (costs). A negative running balance means cumulative spending exceeded revenue to that point.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="cashflow-stmt-app" v-cloak>
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

  <!-- KPI Cards -->
  <div class="row g-3 mb-3" v-if="ran">
    <div class="col-md-4">
      <div class="card border-success">
        <div class="card-body py-3">
          <div class="text-muted small mb-1">Total Inflow</div>
          <div class="fw-bold fs-5 text-success">LKR {{ fmt(totals.total_inflow) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-danger">
        <div class="card-body py-3">
          <div class="text-muted small mb-1">Total Outflow</div>
          <div class="fw-bold fs-5 text-danger">LKR {{ fmt(totals.total_outflow) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card" :class="totals.final_balance >= 0 ? 'border-success' : 'border-danger'">
        <div class="card-body py-3">
          <div class="text-muted small mb-1">Net Balance</div>
          <div class="fw-bold fs-5" :class="totals.final_balance >= 0 ? 'text-success' : 'text-danger'">LKR {{ fmt(totals.final_balance) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" v-if="ran">
    <div class="card-header py-2">
      <strong>Cash Flow Statement — {{ from }} to {{ to }}</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Ref</th>
              <th class="text-end">Inflow (LKR)</th>
              <th class="text-end">Outflow (LKR)</th>
              <th class="text-end">Running Balance (LKR)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in rows" :key="i"
                :class="r.inflow > 0 ? 'table-success' : 'table-danger'"
                style="opacity: 0.85;">
              <td>{{ r.date }}</td>
              <td>{{ r.type }}</td>
              <td>{{ r.ref }}</td>
              <td class="text-end">{{ r.inflow > 0 ? fmt(r.inflow) : '—' }}</td>
              <td class="text-end">{{ r.outflow > 0 ? fmt(r.outflow) : '—' }}</td>
              <td class="text-end fw-bold" :class="r.running_balance >= 0 ? 'text-success' : 'text-danger'">
                {{ fmt(r.running_balance) }}
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="6" class="text-center text-muted py-3">No data for selected period.</td>
            </tr>
          </tbody>
          <tfoot class="fw-bold table-secondary" v-if="rows.length">
            <tr>
              <td colspan="3">Totals</td>
              <td class="text-end text-success">{{ fmt(totals.total_inflow) }}</td>
              <td class="text-end text-danger">{{ fmt(totals.total_outflow) }}</td>
              <td class="text-end" :class="totals.final_balance >= 0 ? 'text-success' : 'text-danger'">
                {{ fmt(totals.final_balance) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/financial_reports/cash_flow_statement/cash_flow_statement.js"></script>
