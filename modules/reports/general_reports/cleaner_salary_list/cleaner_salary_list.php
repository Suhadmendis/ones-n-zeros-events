<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Cleaner Salary List
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Cleaner Salary List</strong> provides a trip-by-trip breakdown of cleaner earnings, showing the amount paid to the cleaner on each trip within the selected date range.
          It answers: <em>"How was each cleaner's pay built up, trip by trip?"</em> — useful for payroll verification and dispute resolution.
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
            <tr><td><code>trips</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Trip reference number</td></tr>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Trip date — used to filter by range</td></tr>
            <tr><td><code>trips</code></td><td><code>cleaner_ref</code></td><td>VARCHAR (FK)</td><td>Links the trip to a specific cleaner</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Total trip revenue (displayed for context)</td></tr>
            <tr><td><code>trips</code></td><td><code>cleaner_salary</code></td><td>NUMERIC</td><td>Cleaner's portion of the trip — the primary salary figure</td></tr>
            <tr><td><code>trips</code></td><td><code>from_loc</code></td><td>VARCHAR</td><td>Departure location — shown in report</td></tr>
            <tr><td><code>trips</code></td><td><code>to_loc</code></td><td>VARCHAR</td><td>Destination location — shown in report</td></tr>
            <tr><td><code>cleaners</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Cleaner reference code — displayed in report</td></tr>
            <tr><td><code>cleaners</code></td><td><code>name</code></td><td>VARCHAR</td><td>Cleaner full name — displayed in report</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter trips</strong> — All trips within the date range are fetched. If a specific cleaner is selected, only their trips are returned.</li>
          <li class="mb-1"><strong>Join cleaners</strong> — A full cleaner list is fetched and used to look up names and refs for each trip.</li>
          <li class="mb-1"><strong>Build rows</strong> — Each trip becomes a row, showing date, cleaner, route, total trip amount, and cleaner amount.</li>
          <li class="mb-1"><strong>Totals</strong> — The footer shows total trip count and total cleaner amount (<code>cleaner_salary</code>) across all rows.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Use this report alongside the Cleaner Advance Report to calculate the net amount payable to each cleaner after deducting advances.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="cleaner-salary-list-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label mb-1">Date From</label><input type="date" class="form-control form-control-sm" v-model="dateFrom" /></div>
        <div class="col-auto"><label class="form-label mb-1">Date To</label><input type="date" class="form-control form-control-sm" v-model="dateTo" /></div>
        <div class="col-auto">
          <label class="form-label mb-1">Cleaner</label>
          <select class="form-select form-select-sm" v-model="cleanerId">
            <option value="">All Cleaners</option>
            <option v-for="c in cleaners" :value="c.ref">{{ c.name }} ({{ c.ref }})</option>
          </select>
        </div>
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
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark"><tr><th>Trip Ref</th><th>Date</th><th>Cleaner</th><th>From</th><th>To</th><th class="text-end">Trip Amount (LKR)</th><th class="text-end">Cleaner Salary (LKR)</th></tr></thead>
        <tbody>
          <tr v-for="r in rows">
            <td>{{ r.ref }}</td><td>{{ r.date }}</td>
            <td>{{ r.cleaner_name }} <small class="text-muted">({{ r.cleaner_ref }})</small></td>
            <td>{{ r.from_loc }}</td><td>{{ r.to_loc }}</td>
            <td class="text-end">{{ fmt(r.trip_amount) }}</td>
            <td class="text-end fw-bold text-success">{{ fmt(r.cleaner_salary) }}</td>
          </tr>
          <tr v-if="!rows.length"><td colspan="7" class="text-center text-muted py-3">No trips for selected period.</td></tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr><td colspan="5">Total ({{ summary.trip_count }} trips)</td><td></td><td class="text-end">{{ fmt(summary.total_cleaner_salary) }}</td></tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/cleaner_salary_list/cleaner_salary_list.js"></script>
