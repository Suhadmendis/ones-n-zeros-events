<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Business Performance Summary
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Business Performance Summary</strong> report gives management a high-level financial snapshot for any date range.
          It answers: <em>"How much did we earn, what did we spend, and what is our estimated profit?"</em> — consolidating revenue, fuel, vehicle maintenance, general expenses, driver payouts, and cleaner payouts into a single dashboard.
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
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Revenue per trip — summed to total revenue</td></tr>
            <tr><td><code>trips</code></td><td><code>driver_salary</code></td><td>NUMERIC</td><td>Driver salary per trip — summed to driver payouts</td></tr>
            <tr><td><code>trips</code></td><td><code>cleaner_salary</code></td><td>NUMERIC</td><td>Cleaner payment — summed to cleaner payouts</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code></td><td>NUMERIC</td><td>Total fuel cost per fill — summed to fuel cost</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Maintenance/repair cost — summed to vehicle expenses</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Overhead expenses — summed to general expenses</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Advance payments issued — shown separately (not subtracted from profit)</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All five source tables are queried for records where <code>date</code> falls between the selected From and To dates.</li>
          <li class="mb-1"><strong>Sum revenue</strong> — <code>amount</code> values from <code>trips</code> are totalled. Trip count is the number of rows returned.</li>
          <li class="mb-1"><strong>Sum driver payouts</strong> — <code>driver_salary</code> per trip is summed across all trips in range.</li>
          <li class="mb-1"><strong>Sum cleaner payouts</strong> — <code>cleaner_salary</code> from trips is totalled separately.</li>
          <li class="mb-1"><strong>Sum cost tables</strong> — <code>fuel_expenses.total</code>, <code>vehicle_expenses.amount</code>, and <code>general_expenses.amount</code> are each independently totalled.</li>
          <li class="mb-1"><strong>Calculate estimated profit</strong> — Revenue minus fuel cost, vehicle expenses, general expenses, driver payouts, and cleaner payouts.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Advance payments are shown for reference only — they are not deducted from the estimated profit figure since they represent cash advances, not operating costs.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="biz-perf-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1 small fw-semibold">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="filters.from">
        </div>
        <div class="col-auto">
          <label class="form-label mb-1 small fw-semibold">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="filters.to">
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

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>

  <template v-if="data">
    <!-- Row 1: Revenue + Cost KPIs -->
    <div class="row g-3 mb-3">
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">Total Revenue</div>
            <div class="fs-4 fw-bold text-success">LKR {{ fmt(data.revenue) }}</div>
            <div class="text-muted small mt-1">{{ data.trip_count }} trips</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">Fuel Cost</div>
            <div class="fs-4 fw-bold text-warning">LKR {{ fmt(data.fuel_cost) }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">Vehicle Expenses</div>
            <div class="fs-4 fw-bold text-danger">LKR {{ fmt(data.vehicle_exp) }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">General Expenses</div>
            <div class="fs-4 fw-bold text-secondary">LKR {{ fmt(data.general_exp) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 2: Payout KPIs -->
    <div class="row g-3 mb-3">
      <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">Driver Payouts</div>
            <div class="fs-4 fw-bold text-info">LKR {{ fmt(data.driver_payouts) }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">Cleaner Payouts</div>
            <div class="fs-4 fw-bold text-info">LKR {{ fmt(data.cleaner_payouts) }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small mb-1">Advance Payments</div>
            <div class="fs-4 fw-bold text-secondary">LKR {{ fmt(data.advance_payments) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 3: Estimated Profit -->
    <div class="row g-3 mb-3">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-4">
            <div class="text-muted mb-2 fw-semibold">Estimated Profit</div>
            <div class="display-5 fw-bold" :class="data.estimated_profit >= 0 ? 'text-success' : 'text-danger'">
              LKR {{ fmt(data.estimated_profit) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Table -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Summary Breakdown</div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light">
            <tr><th>Category</th><th class="text-end">Amount (LKR)</th></tr>
          </thead>
          <tbody>
            <tr><td>Total Revenue</td><td class="text-end text-success">{{ fmt(data.revenue) }}</td></tr>
            <tr><td>Fuel Cost</td><td class="text-end text-danger">{{ fmt(data.fuel_cost) }}</td></tr>
            <tr><td>Vehicle Expenses</td><td class="text-end text-danger">{{ fmt(data.vehicle_exp) }}</td></tr>
            <tr><td>General Expenses</td><td class="text-end text-danger">{{ fmt(data.general_exp) }}</td></tr>
            <tr><td>Driver Payouts</td><td class="text-end text-danger">{{ fmt(data.driver_payouts) }}</td></tr>
            <tr><td>Cleaner Payouts</td><td class="text-end text-danger">{{ fmt(data.cleaner_payouts) }}</td></tr>
            <tr class="table-light fw-bold">
              <td>Estimated Profit</td>
              <td class="text-end" :class="data.estimated_profit >= 0 ? 'text-success' : 'text-danger'">
                {{ fmt(data.estimated_profit) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>
</div>
<script src="/modules/reports/executive_reports/business_performance_summary/business_performance_summary.js"></script>
