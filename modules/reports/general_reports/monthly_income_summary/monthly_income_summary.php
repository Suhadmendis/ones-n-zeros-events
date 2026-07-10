<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Monthly Income Summary
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Monthly Income Summary</strong> report gives management a month-by-month breakdown of total revenue
          and trip volume for a selected year. It answers the question: <em>"How much did we earn and how many trips
          did we complete each month?"</em> — helping identify seasonal trends, peak periods, and slow months at a glance.
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
            <tr>
              <td><code>trips</code></td>
              <td><code>date</code></td>
              <td>DATE (YYYY-MM-DD)</td>
              <td>Used to filter trips by year and group by month</td>
            </tr>
            <tr>
              <td><code>trips</code></td>
              <td><code>amount</code></td>
              <td>NUMERIC</td>
              <td>Revenue per trip — summed to produce monthly totals (LKR)</td>
            </tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1">
            <strong>Filter by year</strong> — All trips where <code>date</code> falls between
            <code>YYYY-01-01</code> and <code>YYYY-12-31</code> are fetched from the <code>trips</code> table via the Supabase REST API.
          </li>
          <li class="mb-1">
            <strong>Group by month</strong> — Each trip's date is truncated to <code>YYYY-MM</code>, and records are
            accumulated into 12 monthly buckets.
          </li>
          <li class="mb-1">
            <strong>Aggregate</strong> — For each month bucket, the system counts total trips
            (<code>trip_count</code>) and sums all <code>amount</code> values (<code>revenue</code>).
          </li>
          <li class="mb-1">
            <strong>Fill all 12 months</strong> — Even months with no trips appear in the table (showing zero), so
            the full year is always visible for comparison.
          </li>
          <li class="mb-1">
            <strong>Totals row</strong> — The footer displays the annual sum of trips and revenue across all
            12 months.
          </li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Revenue figures are rounded to 2 decimal places. All amounts are in Sri Lankan Rupees (LKR).
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="monthly-income-app" v-cloak>
  <!-- filter card -->
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

  <!-- results -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Monthly Income Summary — {{ year }}</strong>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Month</th>
            <th class="text-end">Trips</th>
            <th class="text-end">Revenue (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.month_key">
            <td>{{ r.month_label }}</td>
            <td class="text-end">{{ r.trip_count }}</td>
            <td class="text-end">{{ fmt(r.revenue) }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="3" class="text-center text-muted py-3">No data for selected period.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length">
          <tr>
            <td>Total</td>
            <td class="text-end">{{ totals.trips }}</td>
            <td class="text-end">{{ fmt(totals.revenue) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/monthly_income_summary/monthly_income_summary.js"></script>
