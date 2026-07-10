<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Employee Advance Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Employee Advance Report</strong> lists all advance payments made to employees for a selected
          date range, optionally filtered to a single employee. It answers:
          <em>"Which employees have received advances and how much?"</em> — useful for payroll reconciliation
          and tracking outstanding advance balances.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>m_advance_payments</code></td><td><code>id, ref, date, employee_ref, amount</code></td><td>Various</td><td>Individual advance records</td></tr>
            <tr><td><code>m_employees</code></td><td><code>id, ref, full_name</code></td><td>Various</td><td>Joined to show employee reference and name</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date and employee</strong> — Advance payment records are fetched for the selected period; optionally filtered to one employee.</li>
          <li class="mb-1"><strong>Join employees</strong> — The employees table is fetched and joined by <code>employee_ref</code> to display employee name and reference.</li>
          <li class="mb-1"><strong>Sort by employee name and date</strong> — Results are grouped by employee and then chronologically within each employee.</li>
          <li class="mb-1"><strong>Footer totals</strong> — Entry count and total advance amount are shown in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Use the Employee filter to quickly see the advance history for a specific employee before processing their monthly salary settlement.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="employee-advance-app" v-cloak>
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
          <label class="form-label mb-1">Employee</label>
          <select class="form-select form-select-sm" v-model="employee_id" style="min-width:160px">
            <option value="">All Employees</option>
            <option v-for="e in employees" :key="e.ref" :value="e.ref">{{ e.full_name }} ({{ e.ref }})</option>
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

  <div class="card" v-if="ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Employee Ref</th>
            <th>Employee Name</th>
            <th>Advance Ref</th>
            <th>Date</th>
            <th class="text-end">Amount (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.advance_ref">
            <td>{{ r.employee_ref }}</td>
            <td>{{ r.employee_name }}</td>
            <td>{{ r.advance_ref }}</td>
            <td>{{ r.date }}</td>
            <td class="text-end">{{ fmt(r.amount) }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="5" class="text-center text-muted py-3">No advance payments found for the selected period.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr>
            <td colspan="3">Totals</td>
            <td>{{ summary.entry_count }} entries</td>
            <td class="text-end">{{ fmt(summary.total_amount) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/employee_advance_report/employee_advance_report.js"></script>
