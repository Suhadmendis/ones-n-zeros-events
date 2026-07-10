<?php /* payroll_runs_line_pickers.php — shared row-level pickers for the Payroll Runs employee rows (Employment Record) and their nested component lines (Salary Component). One modal each, reused across every dynamic row. No CDN scripts — depends on jQuery/DataTables already loaded by payroll_runs_search.php. */ ?>

<!-- Employment Record picker (per employee row) -->
<div class="modal fade" id="prEmploymentRecordPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Employment Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="prEmploymentRecordPickerTable" class="table table-bordered table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light"><tr><th>Ref</th><th>Employee</th><th>Employment Type</th></tr></thead>
          </table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- Salary Component picker (per component line, within an employee row) -->
<div class="modal fade" id="prSalaryComponentPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Salary Component</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table id="prSalaryComponentPickerTable" class="table table-bordered table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light"><tr><th>Ref</th><th>Code</th><th>Name</th></tr></thead>
          </table>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<script>
(function () {
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
    'prEmploymentRecordPickerModal', 'prEmploymentRecordPickerTable',
    '/modules/master_files/people/employment_record/employment_record_data.php?action=list',
    [{ data: 'ref' }, { data: 'm_employees.full_name', defaultContent: '' }, { data: 'm_employment_types.name', defaultContent: '' }],
    'pr-employment-record-selected'
  );

  wireLazyPicker(
    'prSalaryComponentPickerModal', 'prSalaryComponentPickerTable',
    '/modules/human_resources_payroll/salary_setup/salary_components/salary_components_data.php?action=list',
    [{ data: 'ref' }, { data: 'code' }, { data: 'name' }],
    'pr-salary-component-selected'
  );
})();
</script>
