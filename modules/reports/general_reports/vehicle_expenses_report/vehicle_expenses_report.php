<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Vehicle Expenses Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Vehicle Expenses Report</strong> lists all maintenance and operational expenses recorded against
          vehicles within a selected date range, with optional filtering by vehicle. It answers:
          <em>"What costs have been charged to each vehicle, broken down by category?"</em> — enabling cost control,
          budget tracking, and vehicle maintenance audits.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicle_expenses</code></td><td><code>ref, date</code></td><td>TEXT / DATE</td><td>Expense reference number and date incurred</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>vehicle_id</code></td><td>INT (FK)</td><td>Links the expense to a specific vehicle</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Cost of the expense in LKR</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>category</code></td><td>TEXT</td><td>Expense category (e.g. fuel, tyre, service)</td></tr>
            <tr><td><code>vehicle_expenses</code></td><td><code>description</code></td><td>TEXT</td><td>Free-text description of the expense</td></tr>
            <tr><td><code>vehicles</code></td><td><code>id, plate_number</code></td><td>INT / TEXT</td><td>Provides the plate number for display</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Filter by date range</strong> — All records in <code>vehicle_expenses</code> where <code>date</code> falls within the selected From–To period are fetched, ordered by date descending.</li>
          <li class="mb-1"><strong>Optional vehicle filter</strong> — If a specific vehicle is selected, an additional <code>vehicle_id=eq.X</code> filter is applied.</li>
          <li class="mb-1"><strong>Look up plate numbers</strong> — The <code>vehicles</code> table is fetched separately; each expense row's vehicle ID is matched to display the plate number.</li>
          <li class="mb-1"><strong>Aggregate by category</strong> — Expenses are grouped by <code>category</code> and summed to produce the category badge totals shown above the table.</li>
          <li class="mb-1"><strong>Build detail rows</strong> — Each expense record becomes one row with ref, date, vehicle, category, amount, and description.</li>
          <li class="mb-1"><strong>Footer total</strong> — The grand total amount and entry count are displayed in the table footer.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> The category badge cards above the table give a quick breakdown of spending by type — useful for spotting which cost categories are dominating.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="veh-expenses-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="from" />
        </div>
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="to" />
        </div>
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Vehicle</label>
          <select class="form-select form-select-sm" v-model="vehicleId" style="min-width:160px">
            <option value="">All Vehicles</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} — {{ v.make }}</option>
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
        <div class="col-auto text-muted small" v-if="summary">
          {{ summary.entry_count }} entries &nbsp;|&nbsp; Total: LKR {{ fmt(summary.total_amount) }}
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

  <!-- Category summary badges -->
  <div class="row g-2 mb-3" v-if="summary && summary.category_totals">
    <div class="col-auto" v-for="(val, cat) in summary.category_totals" :key="cat">
      <div class="card border-0 shadow-sm px-3 py-2 text-center" style="min-width:110px">
        <div class="small text-muted text-capitalize">{{ cat }}</div>
        <div class="fw-bold">LKR {{ fmt(val) }}</div>
      </div>
    </div>
  </div>

  <!-- Results table -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-header py-2">
      <strong>Vehicle Expenses Report</strong>
      <span class="text-muted ms-2 small">{{ from }} to {{ to }}</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Ref</th>
            <th>Date</th>
            <th>Vehicle</th>
            <th>Category</th>
            <th class="text-end">Amount (LKR)</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.ref + r.date">
            <td>{{ r.ref }}</td>
            <td>{{ r.date }}</td>
            <td>{{ r.plate_number }}</td>
            <td><span :class="categoryBadge(r.category)" class="badge">{{ r.category }}</span></td>
            <td class="text-end">{{ fmt(r.amount) }}</td>
            <td>{{ r.description }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="6" class="text-center text-muted py-3">No data for selected period.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length && summary">
          <tr>
            <td colspan="4">Total ({{ summary.entry_count }} entries)</td>
            <td class="text-end">{{ fmt(summary.total_amount) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/vehicle_expenses_report/vehicle_expenses_report.js"></script>
