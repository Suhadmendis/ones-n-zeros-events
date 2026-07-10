<div class="app-content">
  <div class="container-fluid" id="driver-settlement-app">

    <!-- Toolbar -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary btn-sm" @click="newRecord"><i class="bi bi-plus-lg me-1"></i>New</button>
          <button class="btn btn-secondary btn-sm" @click="openSearch"><i class="bi bi-search me-1"></i>Search</button>
          <button class="btn btn-warning btn-sm" @click="editRecord" :disabled="!form.id"><i class="bi bi-pencil me-1"></i>Edit</button>
          <button class="btn btn-info btn-sm" @click="printRecord" :disabled="!form.id"><i class="bi bi-printer me-1"></i>Print</button>
          <button class="btn btn-secondary btn-sm" @click="cancelEdit" :disabled="!editing"><i class="bi bi-x-circle me-1"></i>Cancel</button>
          <button class="btn btn-secondary btn-sm" @click="closeRecord" :disabled="!form.id && !editing"><i class="bi bi-door-closed me-1"></i>Close</button>
          <span class="border-start mx-1"></span>
          <button class="btn btn-danger btn-sm" @click="deleteRecord" :disabled="!form.id || editing"><i class="bi bi-trash me-1"></i>Delete</button>
          <div class="form-check form-switch d-flex align-items-center gap-2 ms-2 mb-0" v-show="financeEnabled">
            <input class="form-check-input" type="checkbox" role="switch" id="checkGLToggle" v-model="checkGL" style="width:2.4em;height:1.25em;cursor:pointer">
            <label class="form-check-label fw-semibold text-nowrap" for="checkGLToggle" style="cursor:pointer;font-size:.85rem">Check GL</label>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>
      </div>
    </div>

    <!-- Journal Entry Preview -->
    <div v-if="checkGL && financeEnabled" class="je-preview mb-3" :class="jeTotalAmount > 0 ? 'je-preview--active' : 'je-preview--empty'">
      <div class="je-preview__header">
        <span class="je-preview__icon"><i class="bi bi-journal-bookmark-fill"></i></span>
        <span class="je-preview__title">Journal Entry Preview</span>
        <span class="je-preview__badge">Auto-Post on Save</span>
      </div>
      <div v-if="jeTotalAmount > 0" class="je-preview__body">
        <table class="je-preview__table">
          <thead><tr>
            <th class="je-preview__th--type">Type</th>
            <th class="je-preview__th--code">Code</th>
            <th class="je-preview__th--name">Account</th>
            <th class="je-preview__th--amount">Debit</th>
            <th class="je-preview__th--amount">Credit</th>
          </tr></thead>
          <tbody>
            <tr class="je-preview__row je-preview__row--dr">
              <td><span class="je-preview__type je-preview__type--dr">DR</span></td>
              <td class="je-preview__code">5300</td>
              <td class="je-preview__name">Driver Salary Expense</td>
              <td class="je-preview__amount je-preview__amount--dr">{{ fmtAmount(jeTotalAmount) }}</td>
              <td class="je-preview__amount je-preview__amount--blank">—</td>
            </tr>
            <tr class="je-preview__row je-preview__row--cr">
              <td><span class="je-preview__type je-preview__type--cr">CR</span></td>
              <td class="je-preview__code">2200</td>
              <td class="je-preview__name">Driver Salaries Payable</td>
              <td class="je-preview__amount je-preview__amount--blank">—</td>
              <td class="je-preview__amount je-preview__amount--cr">{{ fmtAmount(jeTotalAmount) }}</td>
            </tr>
          </tbody>
          <tfoot><tr class="je-preview__total">
            <td colspan="3">Total</td>
            <td class="je-preview__amount">{{ fmtAmount(jeTotalAmount) }}</td>
            <td class="je-preview__amount">{{ fmtAmount(jeTotalAmount) }}</td>
          </tr></tfoot>
        </table>
      </div>
      <div v-else class="je-preview__placeholder">
        <i class="bi bi-pencil-square me-2 opacity-50"></i>Calculate gross earnings above to preview the journal entry.
      </div>
    </div>

    <!-- Alerts -->
    <div v-if="alert.message" :class="'alert alert-' + alert.type + ' alert-dismissible'" role="alert">
      {{ alert.message }}
      <button type="button" class="btn-close" @click="alert.message=''"></button>
    </div>

    <!-- Form -->
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Settlement Details</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">Reference No.</label>
              <input type="text" class="form-control" :value="form.ref || '(Auto)'" disabled />
            </div>
            <div class="mb-3">
              <label class="form-label">Month <span class="text-danger">*</span></label>
              <input type="month" class="form-control" v-model="form.month" :disabled="!editing" @change="onMonthChange" />
            </div>
            <div class="mb-3">
              <label class="form-label">Driver</label>
              <div class="input-group">
                <input type="text" class="form-control" style="max-width:150px" v-model="form.driver_ref" placeholder="Ref" readonly />
                <input type="text" class="form-control" v-model="form.driver_name" placeholder="Driver Name" readonly />
                <button class="btn btn-outline-secondary" type="button" :disabled="!editing" @click="openDriverPicker">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" v-model="form.status" :disabled="!editing">
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3 d-flex justify-content-between align-items-center">
              <label class="form-label mb-0 fw-semibold">Calculated Earnings</label>
              <button class="btn btn-outline-primary btn-sm" @click="calculate" :disabled="!editing || calculating">
                <span v-if="calculating" class="spinner-border spinner-border-sm me-1" role="status"></span>
                <i v-else class="bi bi-calculator me-1"></i>Calculate
              </button>
            </div>
            <div class="mb-3">
              <label class="form-label">Gross Earnings (LKR)</label>
              <input type="text" class="form-control" :value="formatAmount(form.gross_earnings)" disabled />
            </div>
            <div class="mb-3">
              <label class="form-label">Advances (LKR)</label>
              <input type="text" class="form-control" :value="formatAmount(form.advances)" disabled />
            </div>
            <div class="mb-3">
              <label class="form-label">Deductions (LKR)</label>
              <input type="text" class="form-control" :value="formatAmount(form.deductions_total)" disabled />
            </div>
            <div class="mb-3">
              <label class="form-label">Loan Recovery (LKR)</label>
              <input type="text" class="form-control" :value="formatAmount(form.loan_recovery)" disabled />
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Net Payable (LKR)</label>
              <input type="text" class="form-control fw-bold text-success fs-5" :value="formatAmount(form.net_payable)" disabled />
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex align-items-center">
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="saveRecord" :disabled="!editing">
          <span v-if="calculating" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>

    <!-- Driver Picker Modal -->
    <div class="modal fade" id="driverSettlementDriverPickerModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Select Driver</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="text" class="form-control mb-3" v-model="driverPickerSearch" placeholder="Search by name or ref..." />
            <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
              <table class="table table-hover table-sm">
                <thead class="table-light sticky-top">
                  <tr><th>Ref</th><th>Name</th><th>Phone</th><th></th></tr>
                </thead>
                <tbody>
                  <tr v-if="driverPickerLoading"><td colspan="4" class="text-center">Loading...</td></tr>
                  <tr v-for="d in filteredDrivers" :key="d.id" style="cursor:pointer" @click="selectDriver(d)">
                    <td>{{ d.ref }}</td><td>{{ d.name }}</td><td>{{ d.phone }}</td>
                    <td><button class="btn btn-sm btn-primary" @click.stop="selectDriver(d)">Select</button></td>
                  </tr>
                  <tr v-if="!driverPickerLoading && filteredDrivers.length===0"><td colspan="4" class="text-center text-muted">No drivers found</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Modal -->
    <div class="modal fade" id="driverSettlementSearchModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Search Driver Salary Settlements</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <iframe src="/modules/operations/staff_payroll/driver_salary_settlement/driver_salary_settlement_search.php" style="width:100%;height:500px;border:none;"></iframe>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /container -->
</div><!-- /app-content -->

<script src="/modules/operations/staff_payroll/driver_salary_settlement/driver_salary_settlement.js"></script>

<style>
.je-preview { border-radius:8px; overflow:hidden; border:1.5px solid #dee2e6; background:#fff; transition:border-color .2s; }
.je-preview--active { border-color:#0d6efd; }
.je-preview--empty  { border-color:#dee2e6; }
.je-preview__header { display:flex; align-items:center; gap:8px; padding:9px 16px; background:#f8f9fa; border-bottom:1px solid #dee2e6; }
.je-preview--active .je-preview__header { background:#eef3fd; border-bottom-color:#c5d5f7; }
.je-preview__icon  { color:#0d6efd; font-size:.95rem; }
.je-preview__title { font-weight:600; font-size:.82rem; letter-spacing:.02em; color:#1a2340; flex:1; }
.je-preview__badge { font-size:.7rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#fff; background:#0d6efd; padding:2px 9px; border-radius:20px; }
.je-preview__body  { padding:12px 16px 14px; }
.je-preview__placeholder { padding:14px 16px; font-size:.8rem; color:#adb5bd; font-style:italic; }
.je-preview__table { width:100%; border-collapse:separate; border-spacing:0; font-size:.81rem; }
.je-preview__table thead tr { background:#f1f3f5; }
.je-preview__table th { padding:6px 10px; font-weight:600; font-size:.72rem; letter-spacing:.04em; text-transform:uppercase; color:#6c757d; border-bottom:1.5px solid #dee2e6; }
.je-preview__th--type { width:52px; } .je-preview__th--code { width:64px; } .je-preview__th--amount { width:130px; text-align:right; }
.je-preview__row td { padding:7px 10px; border-bottom:1px solid #f1f3f5; }
.je-preview__row--dr td { background:#fff9f9; } .je-preview__row--cr td { background:#f6fff8; }
.je-preview__type { display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.06em; padding:2px 7px; border-radius:4px; }
.je-preview__type--dr { background:#ffe0e0; color:#c0392b; } .je-preview__type--cr { background:#d6f5e0; color:#1a7f45; }
.je-preview__code { font-family:monospace; font-weight:600; color:#495057; } .je-preview__name { color:#212529; }
.je-preview__amount { text-align:right; font-family:monospace; font-weight:500; }
.je-preview__amount--dr { color:#c0392b; } .je-preview__amount--cr { color:#1a7f45; } .je-preview__amount--blank { color:#ced4da; }
.je-preview__total td { padding:7px 10px; font-weight:700; font-size:.8rem; background:#f8f9fa; border-top:1.5px solid #dee2e6; font-family:monospace; text-align:right; }
.je-preview__total td:first-child { text-align:left; font-family:inherit; color:#495057; }
</style>
