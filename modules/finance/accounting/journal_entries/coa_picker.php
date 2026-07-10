<?php /* coa_picker.php — COA account picker modal for Journal Entries. No CDN scripts — depends on jQuery/DataTables already loaded by journal_entries_search.php. */ ?>

<div class="modal fade" id="coaPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-journal-bookmark me-2"></i>Select Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-hash"></i></span>
              <input type="text" id="coa-pick-code" class="form-control" placeholder="Filter by code…" />
            </div>
          </div>
          <div class="col-md-5">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-card-text"></i></span>
              <input type="text" id="coa-pick-name" class="form-control" placeholder="Filter by name…" />
            </div>
          </div>
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-tag"></i></span>
              <input type="text" id="coa-pick-type" class="form-control" placeholder="Filter by type…" />
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="coaPickerTable" class="table table-bordered table-striped table-hover table-sm align-middle" style="width:100%;cursor:pointer">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th>Sub-Type</th>
                <th>Status</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <small class="text-muted me-auto"><i class="bi bi-info-circle me-1"></i>Click a row to select the account</small>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  let pickerTable = null;

  document.getElementById('coaPickerModal').addEventListener('shown.bs.modal', function () {
    if (pickerTable) {
      pickerTable.ajax.reload(null, false);
      return;
    }

    pickerTable = $('#coaPickerTable').DataTable({
      ajax: { url: '/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=list', dataSrc: '' },
      columns: [
        { data: 'account_code',     className: 'font-monospace fw-semibold', width: '10%' },
        { data: 'account_name',     width: '35%' },
        { data: 'account_type',     width: '15%' },
        { data: 'account_sub_type', width: '20%', defaultContent: '—' },
        {
          data: 'status', width: '10%',
          render: (d) => {
            const col = d === 'active' ? 'success' : 'secondary';
            return `<span class="badge text-bg-${col}">${d}</span>`;
          },
        },
      ],
      order: [[0, 'asc']],
      pageLength: 15,
      language: { search: 'Global search:' },
      dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    });

    document.getElementById('coa-pick-code').addEventListener('keyup', function () {
      pickerTable.column(0).search(this.value).draw();
    });
    document.getElementById('coa-pick-name').addEventListener('keyup', function () {
      pickerTable.column(1).search(this.value).draw();
    });
    document.getElementById('coa-pick-type').addEventListener('keyup', function () {
      pickerTable.column(2).search(this.value).draw();
    });

    document.getElementById('coaPickerTable').addEventListener('click', function (e) {
      const tr = e.target.closest('tr');
      if (!tr) return;
      const d = pickerTable.row(tr).data();
      if (!d) return;
      document.dispatchEvent(new CustomEvent('coa-line-selected', {
        detail: { account_code: d.account_code, account_name: d.account_name },
      }));
      bootstrap.Modal.getInstance(document.getElementById('coaPickerModal')).hide();
    });
  });
})();
</script>
