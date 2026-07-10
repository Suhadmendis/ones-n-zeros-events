<?php /* entries/password_change/password_change_search.php — User search modal */ ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous" />

<div class="modal fade" id="passwordChangeSearchModal" tabindex="-1" aria-labelledby="passwordChangeSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="passwordChangeSearchModalLabel">Select User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="pc-search-name" class="form-control" placeholder="Search by name…" />
            </div>
          </div>
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="text" id="pc-search-email" class="form-control" placeholder="Search by email…" />
            </div>
          </div>
        </div>
        <div class="table-responsive">
        <table id="passwordChangeSearchTable" class="table table-bordered table-striped table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr>
              <th>Ref</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Roles</th>
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
<script src="/modules/company_management/user_management/password_change/password_change_search.js"></script>
