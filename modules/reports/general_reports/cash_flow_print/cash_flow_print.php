<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Cash Flow Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Cash Flow Report</strong> provides a line-by-line ledger of all money moving in and out of the business
          for any date range. It answers: <em>"What cash did we receive and pay out, and what is our net position?"</em> — giving finance managers a clear audit trail of cash movements by category.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from a single table:</p>
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
            <tr><td><code>cash_flow_entries</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Unique reference number for each entry</td></tr>
            <tr><td><code>cash_flow_entries</code></td><td><code>date</code></td><td>DATE</td><td>Date of the cash movement — used to filter by range</td></tr>
            <tr><td><code>cash_flow_entries</code></td><td><code>flow_type</code></td><td>VARCHAR</td><td>Either <code>inflow</code> or <code>outflow</code> — determines colour coding and totals</td></tr>
            <tr><td><code>cash_flow_entries</code></td><td><code>category</code></td><td>VARCHAR</td><td>Business category of the cash movement (e.g. revenue, fuel, salary)</td></tr>
            <tr><td><code>cash_flow_entries</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Amount of the transaction in LKR</td></tr>
            <tr><td><code>cash_flow_entries</code></td><td><code>description</code></td><td>TEXT</td><td>Free-text description of the entry</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date</strong> — All <code>cash_flow_entries</code> rows where <code>date</code> is within the selected From–To range are fetched, ordered by date ascending.</li>
          <li class="mb-1"><strong>Separate inflows and outflows</strong> — Each row's <code>flow_type</code> determines whether its amount is added to total inflow or total outflow.</li>
          <li class="mb-1"><strong>Calculate net position</strong> — Net Position = Total Inflow − Total Outflow. Shown in green when positive, red when negative.</li>
          <li class="mb-1"><strong>Display</strong> — Rows are colour-coded (green for inflow, red for outflow) and displayed chronologically.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Cash flow entries are manually recorded transactions. They represent actual cash movements, not accrual accounting entries.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="cash-flow-report-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label mb-1">Date From</label><input type="date" class="form-control form-control-sm" v-model="dateFrom" /></div>
        <div class="col-auto"><label class="form-label mb-1">Date To</label><input type="date" class="form-control form-control-sm" v-model="dateTo" /></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm" @click="load" :disabled="loading"><span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Run Report</button></div>
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
  <div class="row g-3 mb-3" v-if="ran">
    <div class="col-md-4"><div class="card border-start border-success border-3"><div class="card-body py-2"><div class="text-muted small">Total Inflow</div><div class="fs-5 fw-bold text-success">LKR {{ fmt(summary.total_inflow) }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-start border-danger border-3"><div class="card-body py-2"><div class="text-muted small">Total Outflow</div><div class="fs-5 fw-bold text-danger">LKR {{ fmt(summary.total_outflow) }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-start border-3" :class="summary.net_position>=0?'border-success':'border-danger'"><div class="card-body py-2"><div class="text-muted small">Net Position</div><div class="fs-5 fw-bold" :class="summary.net_position>=0?'text-success':'text-danger'">LKR {{ fmt(summary.net_position) }}</div></div></div></div>
  </div>
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-hover mb-0">
        <thead class="table-dark"><tr><th>Ref</th><th>Date</th><th>Flow Type</th><th>Category</th><th class="text-end">Amount (LKR)</th><th>Description</th></tr></thead>
        <tbody>
          <tr v-for="r in rows" :class="r.flow_type==='inflow'?'table-success':'table-danger'" style="opacity:0.9">
            <td>{{ r.ref }}</td><td>{{ r.date }}</td>
            <td><span :class="'badge bg-'+(r.flow_type==='inflow'?'success':'danger')">{{ r.flow_type }}</span></td>
            <td>{{ r.category }}</td>
            <td class="text-end fw-bold" :class="r.flow_type==='inflow'?'text-success':'text-danger'">{{ fmt(r.amount) }}</td>
            <td>{{ r.description }}</td>
          </tr>
          <tr v-if="!rows.length"><td colspan="6" class="text-center text-muted py-3">No cash flow entries for selected period.</td></tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr><td colspan="4">Net Position</td><td class="text-end" :class="summary.net_position>=0?'text-success':'text-danger'">{{ fmt(summary.net_position) }}</td><td></td></tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/cash_flow_print/cash_flow_print.js"></script>
