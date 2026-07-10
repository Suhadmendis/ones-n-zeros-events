<?php
// deduction_search.php — included by home.php alongside the main template
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous" />

<!-- ============================================================
     MAIN: Deduction Search Modal
     ============================================================ -->
<div class="modal fade" id="deductionSearchModal" tabindex="-1" aria-labelledby="deductionSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deductionSearchModalLabel">
          <i class="bi bi-search me-1"></i> Search Deductions
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="deductionSearchTable" class="table table-sm table-hover table-bordered w-100">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Date</th>
              <th>Recipient Type</th>
              <th>Name</th>
              <th>Amount</th>
              <th>Reason</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     INLINE: Driver Picker Modal
     ============================================================ -->
<div class="modal fade" id="deductionDriverPickerModal" tabindex="-1" aria-labelledby="deductionDriverPickerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deductionDriverPickerModalLabel">
          <i class="bi bi-person-badge me-1"></i> Select Driver
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="deductionDriverTable" class="table table-sm table-hover table-bordered w-100">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
     INLINE: Cleaner Picker Modal
     ============================================================ -->
<div class="modal fade" id="deductionCleanerPickerModal" tabindex="-1" aria-labelledby="deductionCleanerPickerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deductionCleanerPickerModalLabel">
          <i class="bi bi-person me-1"></i> Select Cleaner
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="deductionCleanerTable" class="table table-sm table-hover table-bordered w-100">
          <thead class="table-dark">
            <tr>
              <th>Ref</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js" crossorigin="anonymous"></script>
<script src="/modules/operations/staff_payroll/deduction/deduction_search.js"></script>
