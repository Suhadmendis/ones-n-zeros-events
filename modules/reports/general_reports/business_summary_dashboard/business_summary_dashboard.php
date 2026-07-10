<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Business Summary Dashboard
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Business Summary Dashboard</strong> gives a high-level financial overview of the business
          for any selected date range — combining revenue, all major cost categories, and estimated profit
          into a single at-a-glance view. It answers:
          <em>"How did the business perform overall in this period?"</em> — the primary management dashboard
          for owners and senior managers.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>amount, driver_salary, cleaner_salary</code></td><td>NUMERIC</td><td>Revenue, driver payouts, and cleaner payouts</td></tr>
            <tr><td><code>fuel_expenses</code></td><td><code>total</code></td><td>NUMERIC</td><td>Total fuel cost for the period</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Total vehicle maintenance cost</td></tr>
            <tr><td><code>general_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Total overhead cost</td></tr>
            <tr><td><code>advance_payments</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Total advances paid out</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All five tables are queried for the selected period.</li>
          <li class="mb-1"><strong>Sum revenue</strong> — Total trip revenue (amount), driver payouts (driver_salary), and cleaner payouts (cleaner_salary) are summed from the trips table.</li>
          <li class="mb-1"><strong>Sum costs</strong> — Fuel, vehicle expenses, general expenses, and advance payments are each totalled separately.</li>
          <li class="mb-1"><strong>Calculate estimated profit</strong> — <code>profit = revenue − fuel − vehicle expenses − general expenses − driver payouts − cleaner payouts</code>.</li>
          <li class="mb-1"><strong>Display KPI cards and breakdown table</strong> — Each figure is shown in colour-coded KPI cards and a summary table.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Advance payments are shown separately and are NOT deducted from the estimated profit figure — they represent cash flow, not a direct expense. Profit is based on operational costs only.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="biz-summary-app" v-cloak>
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

  <template v-if="ran">
    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="card border-start border-success border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-cash-stack me-1"></i>Total Revenue</div><div class="fs-5 fw-bold text-success">LKR {{ fmt(s.revenue) }}</div><div class="text-muted small">{{ s.trip_count }} trips</div></div></div></div>
      <div class="col-md-3"><div class="card border-start border-warning border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-fuel-pump me-1"></i>Total Fuel Cost</div><div class="fs-5 fw-bold text-warning">LKR {{ fmt(s.fuel_cost) }}</div></div></div></div>
      <div class="col-md-3"><div class="card border-start border-danger border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-truck me-1"></i>Vehicle Expenses</div><div class="fs-5 fw-bold text-danger">LKR {{ fmt(s.vehicle_expenses) }}</div></div></div></div>
      <div class="col-md-3"><div class="card border-start border-secondary border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-receipt me-1"></i>General Expenses</div><div class="fs-5 fw-bold">LKR {{ fmt(s.general_expenses) }}</div></div></div></div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card border-start border-info border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-person-badge me-1"></i>Driver Payouts</div><div class="fs-5 fw-bold text-info">LKR {{ fmt(s.driver_payouts) }}</div></div></div></div>
      <div class="col-md-4"><div class="card border-start border-info border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-person me-1"></i>Cleaner Payouts</div><div class="fs-5 fw-bold text-info">LKR {{ fmt(s.cleaner_payouts) }}</div></div></div></div>
      <div class="col-md-4"><div class="card border-start border-secondary border-3"><div class="card-body py-2"><div class="text-muted small"><i class="bi bi-cash me-1"></i>Advance Payments</div><div class="fs-5 fw-bold">LKR {{ fmt(s.advance_payments) }}</div></div></div></div>
    </div>
    <div class="card mb-3" :class="s.estimated_profit>=0?'border-success':'border-danger'" style="border-width:2px!important">
      <div class="card-body text-center py-4">
        <div class="text-muted mb-1">Estimated Profit (Revenue minus all operating costs)</div>
        <div class="display-6 fw-bold" :class="s.estimated_profit>=0?'text-success':'text-danger'">LKR {{ fmt(s.estimated_profit) }}</div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><strong>Summary Breakdown</strong></div>
      <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light"><tr><th>Category</th><th class="text-end">Amount (LKR)</th></tr></thead>
          <tbody>
            <tr><td>Total Revenue</td><td class="text-end text-success fw-semibold">{{ fmt(s.revenue) }}</td></tr>
            <tr><td>Fuel Cost</td><td class="text-end text-danger">{{ fmt(s.fuel_cost) }}</td></tr>
            <tr><td>Vehicle Expenses</td><td class="text-end text-danger">{{ fmt(s.vehicle_expenses) }}</td></tr>
            <tr><td>General Expenses</td><td class="text-end text-danger">{{ fmt(s.general_expenses) }}</td></tr>
            <tr><td>Driver Payouts</td><td class="text-end text-danger">{{ fmt(s.driver_payouts) }}</td></tr>
            <tr><td>Cleaner Payouts</td><td class="text-end text-danger">{{ fmt(s.cleaner_payouts) }}</td></tr>
          </tbody>
          <tfoot class="fw-bold"><tr :class="s.estimated_profit>=0?'table-success':'table-danger'"><td>Estimated Profit</td><td class="text-end">{{ fmt(s.estimated_profit) }}</td></tr></tfoot>
        </table>
      </div>
    </div>
  </template>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/business_summary_dashboard/business_summary_dashboard.js"></script>
