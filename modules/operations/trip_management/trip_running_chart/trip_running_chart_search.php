<?php /* entries/trip_running_chart/trip_running_chart_search.php — Trip search modal + pickers */ ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous" />

<!-- Vehicle picker modal -->
<div class="modal fade" id="tripVehiclePickerModal" tabindex="-1" aria-labelledby="tripVehiclePickerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tripVehiclePickerLabel">Select Vehicle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="tripVehiclePickerTable" class="table table-bordered table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr><th>Ref</th><th>Plate</th><th>Make</th><th>Model</th><th>Type</th><th>Status</th></tr>
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

<!-- Driver picker modal -->
<div class="modal fade" id="tripDriverPickerModal" tabindex="-1" aria-labelledby="tripDriverPickerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tripDriverPickerLabel">Select Driver</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="tripDriverPickerTable" class="table table-bordered table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr><th>Ref</th><th>Name</th><th>Phone</th><th>License No.</th><th>Status</th></tr>
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

<!-- Cleaner picker modal -->
<div class="modal fade" id="tripCleanerPickerModal" tabindex="-1" aria-labelledby="tripCleanerPickerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tripCleanerPickerLabel">Select Cleaner</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="tripCleanerPickerTable" class="table table-bordered table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr><th>Ref</th><th>Name</th><th>Phone</th><th>Status</th></tr>
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

<!-- Item picker modal -->
<div class="modal fade" id="tripItemPickerModal" tabindex="-1" aria-labelledby="tripItemPickerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tripItemPickerLabel">Select Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="tripItemPickerTable" class="table table-bordered table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr><th>Ref</th><th>Name</th><th>Category</th><th>Unit</th><th>Status</th></tr>
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

<!-- Trip search modal -->
<div class="modal fade" id="tripSearchModal" tabindex="-1" aria-labelledby="tripSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tripSearchModalLabel">Trip / Running Chart Search</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="tripSearchTable" class="table table-bordered table-striped table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr><th>Ref</th><th>Date</th><th>Vehicle</th><th>Driver</th><th>From</th><th>To</th><th>Amount</th><th>Run No.</th></tr>
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
<script src="/modules/operations/trip_management/trip_running_chart/trip_running_chart_search.js"></script>
