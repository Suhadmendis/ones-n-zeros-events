<?php /* work_order_entries_line_pickers.php — shared row-level pickers for the Work Order lines table (Location, Vehicle Type, Vehicle, Driver). One modal each, reused across every dynamic row. No CDN scripts — depends on jQuery/DataTables already loaded by work_order_entries_search.php. */ ?>

<!-- Location picker (per line — shared by pickup and delivery) -->
<div class="modal fade" id="wolLocationPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="wolLocationPickerTable" class="table table-bordered table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light"><tr><th>Ref</th><th>Name</th><th>District</th><th>Province</th></tr></thead>
          </table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- Vehicle Type picker (per line) -->
<div class="modal fade" id="wolVehicleTypePickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Vehicle Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="wolVehicleTypePickerTable" class="table table-bordered table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light"><tr><th>Ref</th><th>Code</th><th>Name</th></tr></thead>
          </table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- Vehicle picker (per line) -->
<div class="modal fade" id="wolVehiclePickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Vehicle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="wolVehiclePickerTable" class="table table-bordered table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light"><tr><th>Ref</th><th>Plate</th><th>Make</th><th>Model</th><th>Type</th><th>Status</th></tr></thead>
          </table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- Driver picker (per line) -->
<div class="modal fade" id="wolDriverPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Driver</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="wolDriverPickerTable" class="table table-bordered table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light"><tr><th>Ref</th><th>Name</th><th>Phone</th><th>License No.</th><th>Status</th></tr></thead>
          </table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<script>
(function () {
  const statusBadge = function (data) {
    const c = { active: 'success', inactive: 'danger' };
    return '<span class="badge text-bg-' + (c[data] || 'secondary') + '">' + data + '</span>';
  };

  function wireLazyPicker(modalId, tableId, ajaxUrl, columns, eventName) {
    let table = null;
    document.getElementById(modalId).addEventListener('shown.bs.modal', function () {
      if (table) { table.ajax.reload(null, false); return; }
      table = $('#' + tableId).DataTable({
        ajax: { url: ajaxUrl, dataSrc: '' },
        columns: columns,
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
        language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
      });
      $('#' + tableId + ' tbody').on('click', 'tr', function () {
        const data = table.row(this).data();
        if (!data) return;
        bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
        document.dispatchEvent(new CustomEvent(eventName, { detail: data }));
      });
    });
  }

  wireLazyPicker(
    'wolLocationPickerModal', 'wolLocationPickerTable',
    '/modules/master_files/references/location_master_file/location_master_file_data.php?action=list',
    [{ data: 'ref' }, { data: 'name' }, { data: 'district' }, { data: 'province' }],
    'wol-location-selected'
  );

  wireLazyPicker(
    'wolVehicleTypePickerModal', 'wolVehicleTypePickerTable',
    '/modules/master_files/vehicle/vehicle_type/vehicle_type_data.php?action=list',
    [{ data: 'ref' }, { data: 'code' }, { data: 'name' }],
    'wol-vehicle-type-selected'
  );

  wireLazyPicker(
    'wolVehiclePickerModal', 'wolVehiclePickerTable',
    '/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=list',
    [
      { data: 'ref' }, { data: 'plate_number' }, { data: 'make' }, { data: 'model' }, { data: 'type' },
      { data: 'status', render: statusBadge },
    ],
    'wol-vehicle-selected'
  );

  wireLazyPicker(
    'wolDriverPickerModal', 'wolDriverPickerTable',
    '/modules/master_files/staff_fleet/driver_master_file/driver_master_file_data.php?action=list',
    [
      { data: 'ref' }, { data: 'name' }, { data: 'phone' }, { data: 'license_number' },
      { data: 'status', render: statusBadge },
    ],
    'wol-driver-selected'
  );
})();
</script>
