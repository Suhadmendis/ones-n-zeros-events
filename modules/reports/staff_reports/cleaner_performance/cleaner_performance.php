<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Cleaner Performance Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Cleaner Performance Report</strong> ranks all cleaners by the number of trips they completed and their total earnings in a date range.
          It answers: <em>"Which cleaners are the most active and how much have they earned?"</em> — useful for identifying top performers and cleaners with no activity.
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
            <tr><td><code>trips</code></td><td><code>cleaner_id</code></td><td>INTEGER (FK)</td><td>Groups trips by cleaner</td></tr>
            <tr><td><code>trips</code></td><td><code>cleaner_salary</code></td><td>NUMERIC</td><td>Cleaner's pay per trip — summed to total earning</td></tr>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Used to filter trips within the selected date range</td></tr>
            <tr><td><code>cleaners</code></td><td><code>id</code></td><td>INTEGER</td><td>Primary key — used to join with trips</td></tr>
            <tr><td><code>cleaners</code></td><td><code>ref</code></td><td>VARCHAR</td><td>Cleaner reference code — displayed in report</td></tr>
            <tr><td><code>cleaners</code></td><td><code>name</code></td><td>VARCHAR</td><td>Cleaner full name — displayed in report</td></tr>
            <tr><td><code>cleaners</code></td><td><code>status</code></td><td>VARCHAR</td><td>Active/inactive status — shown as badge</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Fetch all cleaners</strong> — The full cleaner roster is loaded regardless of date, so cleaners with zero trips still appear.</li>
          <li class="mb-1"><strong>Fetch trips</strong> — Trips within the date range are fetched. If a specific cleaner is selected, only their trips are returned.</li>
          <li class="mb-1"><strong>Aggregate per cleaner</strong> — For each cleaner, trips are counted and <code>cleaner_salary</code> values are summed.</li>
          <li class="mb-1"><strong>Merge with roster</strong> — All cleaners are included in results, with zero values for those with no trips in range.</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by trip count descending, so the most active cleaners appear first.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Cleaners with zero trips in the period are still shown in the report — useful for spotting inactive staff.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="cleaner-perf-app" v-cloak>
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
          <label class="form-label mb-1">Cleaner</label>
          <select class="form-select form-select-sm" v-model="cleaner_id" style="min-width:160px">
            <option value="">All Cleaners</option>
            <option v-for="c in cleaners" :key="c.id" :value="c.id">{{ c.name }} ({{ c.ref }})</option>
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
            <th>Ref</th>
            <th>Name</th>
            <th>Status</th>
            <th class="text-end">Trip Count</th>
            <th class="text-end">Total Cleaner Earning (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.cleaner_ref">
            <td>{{ r.cleaner_ref }}</td>
            <td>{{ r.cleaner_name }}</td>
            <td>
              <span :class="'badge bg-' + (r.status === 'active' ? 'success' : 'secondary')">{{ r.status }}</span>
            </td>
            <td class="text-end">{{ r.trip_count }}</td>
            <td class="text-end">{{ fmt(r.total_cleaner_earning) }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="5" class="text-center text-muted py-3">No data found for the selected period.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr>
            <td colspan="3">Totals ({{ summary.cleaner_count }} cleaners)</td>
            <td class="text-end">{{ summary.total_trips }}</td>
            <td class="text-end">{{ fmt(summary.total_earning) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/staff_reports/cleaner_performance/cleaner_performance.js"></script>
