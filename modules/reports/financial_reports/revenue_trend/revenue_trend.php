<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Revenue Trend
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Revenue Trend</strong> report shows monthly revenue across all 12 months of a selected year,
          including trip counts, average revenue per trip, and month-over-month percentage change. It answers:
          <em>"How is our revenue trending through the year and which months performed best?"</em> — essential for
          spotting seasonal patterns and planning ahead.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>date, amount</code></td><td>DATE, NUMERIC</td><td>Revenue and count aggregated by month</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by year</strong> — All trips within the selected calendar year are fetched.</li>
          <li class="mb-1"><strong>Aggregate by month</strong> — Revenue and trip count are summed per calendar month, ensuring all 12 months appear even if some have zero data.</li>
          <li class="mb-1"><strong>Calculate averages</strong> — Average revenue per trip is computed for each month.</li>
          <li class="mb-1"><strong>MoM change</strong> — Month-over-month percentage change is calculated by comparing each month's revenue to the previous month. The first month shows "—".</li>
          <li class="mb-1"><strong>Chart rendering</strong> — A bar/line chart visualises the full-year revenue trend using ApexCharts.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Green arrows (▲) indicate growth; red arrows (▼) indicate decline vs. the prior month. Use this alongside the expense trend to understand net position changes over time.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div id="rev-trend-app" v-cloak>
  <!-- Filters -->
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

  <!-- Chart -->
  <div class="card mb-3" v-if="ran && rows.length">
    <div class="card-header py-2">
      <strong>Revenue Trend — {{ year }}</strong>
    </div>
    <div class="card-body">
      <div id="revTrendChart"></div>
    </div>
  </div>

  <!-- Table -->
  <div class="card" v-if="ran">
    <div class="card-header py-2">
      <strong>Monthly Revenue — {{ year }}</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>Month</th>
              <th class="text-end">Revenue (LKR)</th>
              <th class="text-end">Trips</th>
              <th class="text-end">Avg / Trip (LKR)</th>
              <th class="text-end">MoM Change %</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.month_label">
              <td>{{ r.month_label }}</td>
              <td class="text-end">{{ fmt(r.revenue) }}</td>
              <td class="text-end">{{ r.trip_count }}</td>
              <td class="text-end">{{ fmt(r.avg_per_trip) }}</td>
              <td class="text-end">
                <span v-if="r.mom_change === null" class="text-muted">—</span>
                <span v-else-if="r.mom_change >= 0" class="text-success">▲ {{ r.mom_change.toFixed(2) }}%</span>
                <span v-else class="text-danger">▼ {{ Math.abs(r.mom_change).toFixed(2) }}%</span>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="5" class="text-center text-muted py-3">No data for selected year.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/financial_reports/revenue_trend/revenue_trend.js"></script>
