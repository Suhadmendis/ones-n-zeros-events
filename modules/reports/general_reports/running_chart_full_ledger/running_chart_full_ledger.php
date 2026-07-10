<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Running Chart / Full Ledger
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Running Chart / Full Ledger</strong> report shows every financial transaction in
          chronological order with a running balance — combining trip revenue, fuel expenses, vehicle expenses,
          general expenses, and advance payments into a single unified ledger. It answers:
          <em>"What is the full financial picture transaction by transaction?"</em> — the go-to report for
          reconciliation and financial auditing.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>id, date, ref, amount</code></td><td>Various</td><td>Inflow — trip revenue entries</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>id, date, ref, total</code></td><td>Various</td><td>Outflow — fuel cost entries</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>id, date, ref, amount</code></td><td>Various</td><td>Outflow — vehicle maintenance entries</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>id, date, ref, amount</code></td><td>Various</td><td>Outflow — overhead expense entries</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>id, date, ref, amount</code></td><td>Various</td><td>Outflow — advance payment entries</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All five tables are queried for the selected From/To period.</li>
          <li class="mb-1"><strong>Normalise to unified rows</strong> — Each record is converted to a common structure: date, type label, ref, inflow amount, outflow amount.</li>
          <li class="mb-1"><strong>Sort chronologically</strong> — All records are merged and sorted by date ascending, then by row ID to break ties.</li>
          <li class="mb-1"><strong>Compute running balance</strong> — Starting from zero, each row adds its inflow and subtracts its outflow to maintain a cumulative running balance.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Grand total inflow, outflow, and final balance are shown in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> A negative running balance (shown in red) indicates the business spent more than it earned up to that point in the period. Use this to pinpoint exactly when cash position turned negative.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="ledger-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="dateFrom">
        </div>
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="dateTo">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            Run Report
          </button>
        </div>
        <div class="col-auto ms-auto text-muted small" v-if="totals">
          {{ rows.length }} entries &nbsp;|&nbsp;
          Balance:
          <span :class="totals.final_balance >= 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold'">
            LKR {{ fmt(totals.final_balance) }}
          </span>
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

  <!-- Results table -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
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
          <tr v-for="r in rows" :key="r.ref + r.date + r.type" :class="rowClass(r)">
            <td>{{ r.date }}</td>
            <td>{{ r.type }}</td>
            <td>{{ r.ref }}</td>
            <td class="text-end">
              <span v-if="r.inflow > 0" class="text-success">{{ fmt(r.inflow) }}</span>
              <span v-else class="text-muted">—</span>
            </td>
            <td class="text-end">
              <span v-if="r.outflow > 0" class="text-danger">{{ fmt(r.outflow) }}</span>
              <span v-else class="text-muted">—</span>
            </td>
            <td class="text-end" :class="balanceClass(r.running_balance)">
              {{ fmt(r.running_balance) }}
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="6" class="text-center text-muted py-3">No data.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length && totals">
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

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/running_chart_full_ledger/running_chart_full_ledger.js"></script>
