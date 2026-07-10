<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Department Revenue
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Department Revenue</strong> report breaks down total trip revenue by business department for a
          selected date range. It answers: <em>"Which department generates the most revenue?"</em> — helping management
          understand the contribution of each division to overall income.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from a single table:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>department</code></td><td>TEXT</td><td>Grouping key for revenue breakdown</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Revenue per trip — summed per department</td></tr>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Filters trips to the selected date range</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All trips within the selected From/To dates are fetched.</li>
          <li class="mb-1"><strong>Group by department</strong> — Trips with no department are placed in an "Unassigned" bucket.</li>
          <li class="mb-1"><strong>Aggregate</strong> — Trip count and total revenue are summed per department.</li>
          <li class="mb-1"><strong>Calculate share</strong> — Each department's revenue is expressed as a percentage of the grand total.</li>
          <li class="mb-1"><strong>Sort</strong> — Departments are ranked by total revenue descending.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Trips without a department are grouped as "Unassigned". Ensure trips are tagged with the correct department for accurate reporting.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="dept-revenue-app" v-cloak>
  <!-- filters -->
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

  <div v-if="rows.length || ran">
    <!-- chart -->
    <div class="card mb-3" v-if="rows.length">
      <div class="card-header py-2"><strong>Revenue by Department</strong></div>
      <div class="card-body">
        <div id="deptRevenueChart" style="height:280px;"></div>
      </div>
    </div>

    <!-- table -->
    <div class="card">
      <div class="card-header py-2">
        <strong>Department Revenue Report</strong>
        <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mb-0">
            <thead class="table-dark">
              <tr>
                <th>Department</th>
                <th class="text-center">Trip Count</th>
                <th class="text-end">Total Revenue (LKR)</th>
                <th class="text-end">% of Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in rows" :key="r.department">
                <td>{{ r.department }}</td>
                <td class="text-center">{{ r.trip_count }}</td>
                <td class="text-end fw-semibold">{{ fmt(r.total_revenue) }}</td>
                <td class="text-end">{{ r.pct_of_total }}%</td>
              </tr>
              <tr v-if="!rows.length">
                <td colspan="4" class="text-center text-muted py-3">No data for selected period.</td>
              </tr>
            </tbody>
            <tfoot class="fw-bold table-secondary" v-if="rows.length">
              <tr>
                <td>Total</td>
                <td class="text-center">{{ totalTrips }}</td>
                <td class="text-end">{{ fmt(grandTotal) }}</td>
                <td class="text-end">100%</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/modules/reports/operations_reports/department_revenue/department_revenue.js"></script>
