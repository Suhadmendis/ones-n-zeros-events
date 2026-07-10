<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: General Expenses Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>General Expenses Report</strong> lists all overhead and miscellaneous expenses recorded
          for a selected date range. It answers:
          <em>"What general business expenses were incurred and what was the total spend?"</em> — useful for
          overhead cost tracking and financial audits.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>general_expenses</code></td><td><code>ref, date, amount, description</code></td><td>Various</td><td>One row per general expense entry</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All general expense records within the selected From/To dates are fetched.</li>
          <li class="mb-1"><strong>Sort by date descending</strong> — Most recent expenses appear first.</li>
          <li class="mb-1"><strong>Compute totals</strong> — Entry count and total amount are summed and shown in the filter bar and table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Use the description column to identify recurring overhead categories. Compare monthly totals to spot unusual spikes in general expenditure.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="gen-expenses-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label mb-1">Date From</label><input type="date" class="form-control form-control-sm" v-model="dateFrom" /></div>
        <div class="col-auto"><label class="form-label mb-1">Date To</label><input type="date" class="form-control form-control-sm" v-model="dateTo" /></div>
        <div class="col-auto"><button class="btn btn-primary btn-sm" @click="load" :disabled="loading"><span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Run Report</button></div>
        <div class="col-auto ms-auto" v-if="summary.entry_count"><small class="text-muted">{{ summary.entry_count }} entries &mdash; Total: <strong>LKR {{ fmt(summary.total_amount) }}</strong></small></div>
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
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark"><tr><th>Ref</th><th>Date</th><th class="text-end">Amount (LKR)</th><th>Description</th></tr></thead>
        <tbody>
          <tr v-for="r in rows"><td>{{ r.ref }}</td><td>{{ r.date }}</td><td class="text-end">{{ fmt(r.amount) }}</td><td>{{ r.description }}</td></tr>
          <tr v-if="!rows.length"><td colspan="4" class="text-center text-muted py-3">No expenses for selected period.</td></tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length"><tr><td colspan="2">Total</td><td class="text-end">{{ fmt(summary.total_amount) }}</td><td></td></tr></tfoot>
      </table>
    </div>
  </div>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/general_expenses_report/general_expenses_report.js"></script>
