<!-- Report Info Modal -->
<div class="modal fade" id="reportInfoModal" tabindex="-1" aria-labelledby="reportInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reportInfoModalLabel">
          <i class="bi bi-info-circle me-2 text-primary"></i>About: My Trips
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-bullseye me-1"></i>Purpose</h6>
        <p class="mb-3">
          The <strong>My Trips</strong> report shows an employee their own trip history — dates, routes,
          and per-trip earnings. It answers:
          <em>"What trips have I completed and what did I earn per trip?"</em> — designed for individual
          employee self-service once the employee login system is live.
        </p>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-database me-1"></i>Database Structure</h6>
        <p class="mb-1">This report will draw from the following tables (once employee sessions are enabled):</p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr><th>Table</th><th>Column</th><th>Type</th><th>Role in this report</th></tr>
          </thead>
          <tbody>
            <tr><td><code>trips</code></td><td><code>ref, date, from_loc, to_loc, driver_salary, cleaner_salary, run_no</code></td><td>Various</td><td>Trip records for the logged-in employee</td></tr>
            <tr><td><code>drivers</code></td><td><code>id, name</code></td><td>Various</td><td>Identifies the logged-in driver employee</td></tr>
            <tr><td><code>cleaners</code></td><td><code>id, name</code></td><td>Various</td><td>Identifies the logged-in cleaner employee</td></tr>
          </tbody>
        </table>

        <hr>

        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-gear me-1"></i>How Data Is Gathered</h6>
        <ol class="mb-3">
          <li class="mb-1"><strong>Session required</strong> — The employee's ID and type (driver/cleaner) are read from their login session.</li>
          <li class="mb-1"><strong>Fetch trips</strong> — All trip records matching the session employee ID are fetched, optionally filtered by month.</li>
          <li class="mb-1"><strong>Display per-trip earnings</strong> — Driver salary (driver_salary) or cleaner salary (cleaner_salary) is shown per trip along with route details.</li>
        </ol>

        <div class="alert alert-info mb-0 py-2">
          <i class="bi bi-lightbulb me-1"></i>
          <strong>Tip:</strong> This report requires an active employee login session. The session system is currently under development and will be available in a future release.
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="my-trips-app">
  <div class="alert alert-info d-flex align-items-center gap-2">
    <i class="bi bi-person-lock fs-4"></i>
    <div><strong>My Trips</strong><br>This report is available to logged-in employees only. Employee login system coming soon.</div>
  </div>
  <div class="mt-2">
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
<script src="/modules/reports/payroll_reports/my_trips/my_trips.js"></script>
