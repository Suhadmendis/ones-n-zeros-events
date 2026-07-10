<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />

<div class="app-content" id="loan-app">
    <div class="container-fluid">

        <!-- Alerts -->
        <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ error }}
            <button type="button" class="btn-close" @click="error=''"></button>
        </div>
        <div v-if="success" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ success }}
            <button type="button" class="btn-close" @click="success=''"></button>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-1">
                    <div class="btn-group me-2">
                        <button class="btn btn-sm btn-primary" @click="newRecord" :disabled="loading">
                            <i class="bi bi-file-earmark-plus"></i> New
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="openSearch">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="printRecord" :disabled="!form.ref">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="resetForm">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="resetForm">
                            <i class="bi bi-door-closed"></i> Close
                        </button>
                    </div>
                    <div class="form-check form-switch d-flex align-items-center gap-2 ms-2 mb-0" v-show="financeEnabled">
                        <input class="form-check-input" type="checkbox" role="switch" id="checkGLToggle" v-model="checkGL" style="width:2.4em;height:1.25em;cursor:pointer">
                        <label class="form-check-label fw-semibold text-nowrap" for="checkGLToggle" style="cursor:pointer;font-size:.85rem">Check GL</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto module-help-btn" title="Help">
                        <i class="bi bi-question-circle me-1"></i>Help
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div v-if="loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <form v-else @submit.prevent="saveRecord">
                    <!-- Journal Entry Preview -->
                    <div v-if="checkGL && financeEnabled" class="je-preview mb-4" :class="jeTotalAmount > 0 ? 'je-preview--active' : 'je-preview--empty'">
                      <div class="je-preview__header">
                        <span class="je-preview__icon"><i class="bi bi-journal-bookmark-fill"></i></span>
                        <span class="je-preview__title">Journal Entry Preview</span>
                        <span class="je-preview__badge">Auto-Post on Save</span>
                      </div>
                      <div v-if="jeTotalAmount > 0" class="je-preview__body">
                        <table class="je-preview__table">
                          <thead>
                            <tr>
                              <th class="je-preview__th--type">Type</th>
                              <th class="je-preview__th--code">Code</th>
                              <th class="je-preview__th--name">Account</th>
                              <th class="je-preview__th--amount">Debit</th>
                              <th class="je-preview__th--amount">Credit</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr class="je-preview__row je-preview__row--dr">
                              <td><span class="je-preview__type je-preview__type--dr">DR</span></td>
                              <td class="je-preview__code">1220</td>
                              <td class="je-preview__name">Staff Loans Receivable</td>
                              <td class="je-preview__amount je-preview__amount--dr">{{ fmtAmount(jeTotalAmount) }}</td>
                              <td class="je-preview__amount je-preview__amount--blank">—</td>
                            </tr>
                            <tr class="je-preview__row je-preview__row--cr">
                              <td><span class="je-preview__type je-preview__type--cr">CR</span></td>
                              <td class="je-preview__code">1100</td>
                              <td class="je-preview__name">Cash in Hand</td>
                              <td class="je-preview__amount je-preview__amount--blank">—</td>
                              <td class="je-preview__amount je-preview__amount--cr">{{ fmtAmount(jeTotalAmount) }}</td>
                            </tr>
                          </tbody>
                          <tfoot>
                            <tr class="je-preview__total">
                              <td colspan="3">Total</td>
                              <td class="je-preview__amount">{{ fmtAmount(jeTotalAmount) }}</td>
                              <td class="je-preview__amount">{{ fmtAmount(jeTotalAmount) }}</td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                      <div v-else class="je-preview__placeholder">
                        <i class="bi bi-pencil-square me-2 opacity-50"></i>Enter an amount above to preview the journal entry.
                      </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reference No.</label>
                                <input type="text" class="form-control font-monospace" :value="form.ref" disabled placeholder="Auto-generated">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.date" :disabled="mode === 'view'">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Recipient Type <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.recipient_type" :disabled="mode === 'view'">
                                    <option value="driver">Driver</option>
                                    <option value="cleaner">Cleaner</option>
                                </select>
                            </div>
                            <div v-if="form.recipient_type === 'driver'" class="mb-3">
                                <label class="form-label">Driver <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" v-model="form.driver_ref" readonly placeholder="Ref" style="max-width:100px">
                                    <input type="text" class="form-control" v-model="form.driver_name" readonly placeholder="Driver name">
                                    <button type="button" class="btn btn-outline-secondary" @click="openDriverPicker" :disabled="mode === 'view'">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-if="form.recipient_type === 'cleaner'" class="mb-3">
                                <label class="form-label">Cleaner <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" v-model="form.cleaner_ref" readonly placeholder="Ref" style="max-width:100px">
                                    <input type="text" class="form-control" v-model="form.cleaner_name" readonly placeholder="Cleaner name">
                                    <button type="button" class="btn btn-outline-secondary" @click="openCleanerPicker" :disabled="mode === 'view'">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Principal Amount (LKR) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" v-model="form.principal_amount" step="0.01" min="0" :disabled="mode === 'view'" placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Recovered Amount (LKR)</label>
                                <input type="number" class="form-control" v-model="form.recovered_amount" step="0.01" min="0" :disabled="mode === 'view'" placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" v-model="form.status" :disabled="mode === 'view'">
                                    <option value="active">Active</option>
                                    <option value="settled">Settled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer d-flex align-items-center">
              <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="saveRecord" :disabled="saving || mode === 'view'">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i v-else class="bi bi-check-lg me-2"></i>Save
              </button>
            </div>
        </div>

    </div>
</div>

<!-- Driver Picker Modal -->
<div class="modal fade" id="loanDriverPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Select Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table id="loanDriverTable" class="table table-sm table-hover table-bordered w-100">
                    <thead><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Status</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Cleaner Picker Modal -->
<div class="modal fade" id="loanCleanerPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person me-2"></i>Select Cleaner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table id="loanCleanerTable" class="table table-sm table-hover table-bordered w-100">
                    <thead><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Status</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="loanSearchModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-search me-2"></i>Search Loans</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table id="loanSearchTable" class="table table-sm table-hover table-bordered w-100">
                    <thead><tr><th>Ref</th><th>Date</th><th>Type</th><th>Recipient Name</th><th>Principal</th><th>Recovered</th><th>Status</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>

<script src="/modules/operations/staff_payroll/loan/loan.js"></script>

<script>
let loanDriverDT = null;
document.getElementById('loanDriverPickerModal').addEventListener('show.bs.modal', function () {
    if (loanDriverDT) { loanDriverDT.ajax.reload(); return; }
    loanDriverDT = $('#loanDriverTable').DataTable({
        ajax: { url: '/modules/operations/loan/loan_data.php?action=list_drivers', dataSrc: '' },
        columns: [
            { data: 'ref' },
            { data: 'name' },
            { data: 'phone', defaultContent: '' },
            { data: 'status' }
        ],
        pageLength: 10,
        responsive: true
    });
    $('#loanDriverTable tbody').on('click', 'tr', function () {
        const row = loanDriverDT.row(this).data();
        window.dispatchEvent(new CustomEvent('loan-driver-selected', { detail: row }));
        bootstrap.Modal.getInstance(document.getElementById('loanDriverPickerModal')).hide();
    });
});

let loanCleanerDT = null;
document.getElementById('loanCleanerPickerModal').addEventListener('show.bs.modal', function () {
    if (loanCleanerDT) { loanCleanerDT.ajax.reload(); return; }
    loanCleanerDT = $('#loanCleanerTable').DataTable({
        ajax: { url: '/modules/operations/loan/loan_data.php?action=list_cleaners', dataSrc: '' },
        columns: [
            { data: 'ref' },
            { data: 'name' },
            { data: 'phone', defaultContent: '' },
            { data: 'status' }
        ],
        pageLength: 10,
        responsive: true
    });
    $('#loanCleanerTable tbody').on('click', 'tr', function () {
        const row = loanCleanerDT.row(this).data();
        window.dispatchEvent(new CustomEvent('loan-cleaner-selected', { detail: row }));
        bootstrap.Modal.getInstance(document.getElementById('loanCleanerPickerModal')).hide();
    });
});

let loanSearchDT = null;
document.getElementById('loanSearchModal').addEventListener('show.bs.modal', function () {
    if (loanSearchDT) { loanSearchDT.ajax.reload(); return; }
    loanSearchDT = $('#loanSearchTable').DataTable({
        ajax: { url: '/modules/operations/loan/loan_data.php?action=list', dataSrc: '' },
        columns: [
            { data: 'ref' },
            { data: 'date' },
            { data: 'recipient_type', render: d => d.charAt(0).toUpperCase() + d.slice(1) },
            { data: 'recipient_name', defaultContent: '' },
            { data: 'principal_amount', render: d => parseFloat(d).toLocaleString('en-LK', {minimumFractionDigits:2}) },
            { data: 'recovered_amount', render: d => parseFloat(d).toLocaleString('en-LK', {minimumFractionDigits:2}) },
            { data: 'status', render: d => `<span class="badge bg-${d==='active'?'success':'secondary'}">${d}</span>` }
        ],
        pageLength: 15,
        responsive: true
    });
    $('#loanSearchTable tbody').on('click', 'tr', function () {
        const row = loanSearchDT.row(this).data();
        window.dispatchEvent(new CustomEvent('loan-selected', { detail: row }));
        bootstrap.Modal.getInstance(document.getElementById('loanSearchModal')).hide();
    });
});
</script>

<style>
.je-preview {
  border-radius: 8px;
  overflow: hidden;
  border: 1.5px solid #dee2e6;
  background: #fff;
  transition: border-color .2s;
}
.je-preview--active  { border-color: #0d6efd; }
.je-preview--empty   { border-color: #dee2e6; }
.je-preview__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.je-preview--active .je-preview__header {
  background: #eef3fd;
  border-bottom-color: #c5d5f7;
}
.je-preview__icon   { color: #0d6efd; font-size: .95rem; }
.je-preview__title  { font-weight: 600; font-size: .82rem; letter-spacing: .02em; color: #1a2340; flex: 1; }
.je-preview__badge  {
  font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
  color: #fff; background: #0d6efd; padding: 2px 9px; border-radius: 20px;
}
.je-preview__body    { padding: 12px 16px 14px; }
.je-preview__placeholder {
  padding: 14px 16px; font-size: .8rem; color: #adb5bd; font-style: italic;
}
.je-preview__table {
  width: 100%; border-collapse: separate; border-spacing: 0; font-size: .81rem;
}
.je-preview__table thead tr { background: #f1f3f5; }
.je-preview__table th {
  padding: 6px 10px; font-weight: 600; font-size: .72rem; letter-spacing: .04em;
  text-transform: uppercase; color: #6c757d; border-bottom: 1.5px solid #dee2e6;
}
.je-preview__th--type   { width: 52px; }
.je-preview__th--code   { width: 64px; }
.je-preview__th--name   { }
.je-preview__th--amount { width: 130px; text-align: right; }
.je-preview__row td      { padding: 7px 10px; border-bottom: 1px solid #f1f3f5; }
.je-preview__row--dr td  { background: #fff9f9; }
.je-preview__row--cr td  { background: #f6fff8; }
.je-preview__type {
  display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .06em;
  padding: 2px 7px; border-radius: 4px;
}
.je-preview__type--dr { background: #ffe0e0; color: #c0392b; }
.je-preview__type--cr { background: #d6f5e0; color: #1a7f45; }
.je-preview__code   { font-family: monospace; font-weight: 600; color: #495057; }
.je-preview__name   { color: #212529; }
.je-preview__amount        { text-align: right; font-family: monospace; font-weight: 500; }
.je-preview__amount--dr    { color: #c0392b; }
.je-preview__amount--cr    { color: #1a7f45; }
.je-preview__amount--blank { color: #ced4da; }
.je-preview__total td {
  padding: 7px 10px; font-weight: 700; font-size: .8rem; background: #f8f9fa;
  border-top: 1.5px solid #dee2e6; font-family: monospace; text-align: right;
}
.je-preview__total td:first-child {
  text-align: left; font-family: inherit; color: #495057;
}
</style>
