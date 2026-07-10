<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: Lorry vs Trips Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>Lorry vs Trips Report</strong> shows every vehicle in the fleet alongside its trip count, revenue,
          and mileage for a selected period. It answers: <em>"How many trips did each lorry complete, and which vehicles
          are sitting idle?"</em> — helping management monitor fleet utilisation and identify underperforming assets.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report draws from the following tables:</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>vehicles</code></td><td><code>id, ref, plate_number, make, model, status</code></td><td>Various</td><td>Master vehicle list (all vehicles appear even with zero trips)</td></tr>
            <tr><td><code>trips</code></td><td><code>vehicle_id</code></td><td>FK</td><td>Links trips to vehicles</td></tr>
            <tr><td><code>trips</code></td><td><code>amount</code></td><td>NUMERIC</td><td>Revenue per trip — summed per vehicle</td></tr>
            <tr><td><code>trips</code></td><td><code>mileage</code></td><td>NUMERIC</td><td>Distance per trip — summed per vehicle</td></tr>
            <tr><td><code>trips</code></td><td><code>date</code></td><td>DATE</td><td>Filters trips to selected date range</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Fetch all vehicles</strong> — The complete vehicle list is retrieved from <code>vehicles</code>, ordered by ref.</li>
          <li class="mb-1"><strong>Fetch trips in range</strong> — All trips within the selected date range are fetched and aggregated by <code>vehicle_id</code>.</li>
          <li class="mb-1"><strong>Join and fill zeros</strong> — Every vehicle appears in the output; vehicles with no trips show zeros (highlighted in yellow).</li>
          <li class="mb-1"><strong>Sort</strong> — Results are sorted by trip count descending so the most-used vehicles appear first.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> Rows highlighted in yellow indicate vehicles with zero trips in the selected period — these may be idle, under maintenance, or unassigned.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="lorry-trips-app" v-cloak>
  <!-- Filters -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="dateFrom">
        </div>
        <div class="col-auto">
          <label class="form-label form-label-sm mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="dateTo">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            Run Report
          </button>
        </div>
        <div class="col-auto ms-auto text-muted small" v-if="totals">
          {{ rows.length }} vehicle(s) &nbsp;|&nbsp; {{ totals.trip_count }} trips
        </div>
        <div class="col-auto">
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

  <!-- Results table -->
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>Ref</th>
            <th>Plate</th>
            <th>Make</th>
            <th>Model</th>
            <th>Status</th>
            <th class="text-center">Trips</th>
            <th class="text-end">Revenue (LKR)</th>
            <th class="text-end">Mileage (km)</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="r in rows"
            :key="r.id"
            :class="r.trip_count === 0 ? 'table-warning' : ''"
          >
            <td>{{ r.ref }}</td>
            <td>{{ r.plate_number }}</td>
            <td>{{ r.make }}</td>
            <td>{{ r.model }}</td>
            <td>
              <span
                class="badge"
                :class="r.status === 'active' ? 'bg-success' : 'bg-secondary'"
              >{{ r.status }}</span>
            </td>
            <td class="text-center">
              <span :class="r.trip_count === 0 ? 'text-muted' : ''">{{ r.trip_count }}</span>
            </td>
            <td class="text-end">{{ fmt(r.total_revenue) }}</td>
            <td class="text-end">{{ fmtNum(r.total_mileage) }}</td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="8" class="text-center text-muted py-3">No data.</td>
          </tr>
        </tbody>
        <tfoot class="fw-bold table-secondary" v-if="rows.length && totals">
          <tr>
            <td colspan="5">Totals ({{ rows.length }} vehicles)</td>
            <td class="text-center">{{ totals.trip_count }}</td>
            <td class="text-end">{{ fmt(totals.total_revenue) }}</td>
            <td class="text-end">{{ fmtNum(totals.total_mileage) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/reports/general_reports/lorry_vs_trips_report/lorry_vs_trips_report.js"></script>
