<?php /* modules/finance/financial_reports/financial_reports.php — Financial Reports hub */ ?>

<div class="row g-4">

  <div class="col-12">
    <p class="text-muted mb-1">Select a report to view financial analysis and summaries.</p>
  </div>

  <!-- Income & Profit -->
  <div class="col-12">
    <h6 class="text-uppercase text-muted fw-semibold small mb-3 border-bottom pb-1">Income &amp; Profit</h6>
  </div>

  <div class="col-md-4">
    <a href="?page=monthly_profit" class="text-decoration-none">
      <div class="card card-outline card-success h-100 report-card">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="fs-2 text-success"><i class="bi bi-graph-up-arrow"></i></div>
          <div>
            <div class="fw-semibold">Monthly Profit</div>
            <div class="text-muted small">Net profit/loss by month vs. operating expenses.</div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4">
    <a href="?page=income_vs_expense" class="text-decoration-none">
      <div class="card card-outline card-primary h-100 report-card">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="fs-2 text-primary"><i class="bi bi-bar-chart-line"></i></div>
          <div>
            <div class="fw-semibold">Income vs Expense</div>
            <div class="text-muted small">Side-by-side revenue and expense comparison.</div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4">
    <a href="?page=revenue_trend" class="text-decoration-none">
      <div class="card card-outline card-info h-100 report-card">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="fs-2 text-info"><i class="bi bi-trend-up"></i></div>
          <div>
            <div class="fw-semibold">Revenue Trend</div>
            <div class="text-muted small">Revenue trend over time with period comparison.</div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <!-- Expenses -->
  <div class="col-12 mt-2">
    <h6 class="text-uppercase text-muted fw-semibold small mb-3 border-bottom pb-1">Expenses</h6>
  </div>

  <div class="col-md-4">
    <a href="?page=expense_summary" class="text-decoration-none">
      <div class="card card-outline card-warning h-100 report-card">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="fs-2 text-warning"><i class="bi bi-receipt-cutoff"></i></div>
          <div>
            <div class="fw-semibold">Expense Summary</div>
            <div class="text-muted small">Total expenses broken down by category.</div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-4">
    <a href="?page=expense_trend" class="text-decoration-none">
      <div class="card card-outline card-danger h-100 report-card">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="fs-2 text-danger"><i class="bi bi-graph-down-arrow"></i></div>
          <div>
            <div class="fw-semibold">Expense Trend</div>
            <div class="text-muted small">Expense movement over selected periods.</div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <!-- Cash Flow -->
  <div class="col-12 mt-2">
    <h6 class="text-uppercase text-muted fw-semibold small mb-3 border-bottom pb-1">Cash Flow</h6>
  </div>

  <div class="col-md-4">
    <a href="?page=cash_flow_statement" class="text-decoration-none">
      <div class="card card-outline card-secondary h-100 report-card">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="fs-2 text-secondary"><i class="bi bi-cash-stack"></i></div>
          <div>
            <div class="fw-semibold">Cash Flow Statement</div>
            <div class="text-muted small">Operating, investing, and financing cash flows.</div>
          </div>
        </div>
      </div>
    </a>
  </div>

</div>

<style>
.report-card { transition: box-shadow 0.15s, transform 0.15s; cursor: pointer; }
.report-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
</style>
