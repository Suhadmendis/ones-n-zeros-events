<?php /* modules/finance/journal_entries/journal_entries_search.php — JE search modal */ ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous" />

<div class="modal fade" id="jeSearchModal" tabindex="-1" aria-labelledby="jeSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="jeSearchModalLabel">Journal Entries Search</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
              <input type="text" id="je-search-period" class="form-control" placeholder="Search by period…" />
            </div>
          </div>
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-card-text"></i></span>
              <input type="text" id="je-search-desc" class="form-control" placeholder="Search by description…" />
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-circle"></i></span>
              <input type="text" id="je-search-status" class="form-control" placeholder="Search by status…" />
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="jeSearchTable" class="table table-bordered table-striped table-hover align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light">
              <tr>
                <th>Ref</th>
                <th>Date</th>
                <th>Period</th>
                <th>Description</th>
                <th>Ref Doc</th>
                <th>Total Debit</th>
                <th>Total Credit</th>
                <th>Status</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
<script src="/modules/finance/accounting/journal_entries/journal_entries_search.js"></script>
