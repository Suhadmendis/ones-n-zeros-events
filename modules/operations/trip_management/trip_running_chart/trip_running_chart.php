<?php /* entries/trip_running_chart/trip_running_chart.php — Trip / Running Chart form, included by home.php */ ?>

<div id="trip-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tripSearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <div class="form-check form-switch d-flex align-items-center gap-2 ms-2 mb-0" v-show="financeEnabled">
            <input class="form-check-input" type="checkbox" role="switch" id="checkGLToggle" v-model="checkGL" style="width:2.4em;height:1.25em;cursor:pointer">
            <label class="form-check-label fw-semibold text-nowrap" for="checkGLToggle" style="cursor:pointer;font-size:.85rem">Check GL</label>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

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
                  <td class="je-preview__code">1200</td>
                  <td class="je-preview__name">Accounts Receivable</td>
                  <td class="je-preview__amount je-preview__amount--dr">{{ fmtAmount(jeTotalAmount) }}</td>
                  <td class="je-preview__amount je-preview__amount--blank">—</td>
                </tr>
                <tr class="je-preview__row je-preview__row--cr">
                  <td><span class="je-preview__type je-preview__type--cr">CR</span></td>
                  <td class="je-preview__code">4000</td>
                  <td class="je-preview__name">Transport Revenue</td>
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

        <div class="row mt-2 g-4">

          <!-- Left column: Ref + all entity pickers -->
          <div class="col-md-6">

            <div class="row mb-3">
              <label for="trp-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="trp-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Vehicle</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" v-model="form.vehicle_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.vehicle_plate" placeholder="Plate number…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#tripVehiclePickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Driver</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" v-model="form.driver_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.driver_name" placeholder="Driver name…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#tripDriverPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Cleaner <span class="text-muted small">(opt.)</span></label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" v-model="form.cleaner_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.cleaner_name" placeholder="Cleaner name…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#tripCleanerPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearCleaner" title="Clear">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Item <span class="text-muted small">(opt.)</span></label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" v-model="form.item_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.item_name_display" placeholder="Item name…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#tripItemPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearItem" title="Clear">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-item-name" class="col-sm-4 col-form-label">Item Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="trp-item-name" placeholder="Item description…" v-model="form.item_name" />
              </div>
            </div>

          </div>

          <!-- Right column: Date, KM, route, amounts, misc -->
          <div class="col-md-6">

            <div class="row mb-3">
              <label for="trp-date" class="col-sm-4 col-form-label">Date</label>
              <div class="col-sm-5">
                <input type="date" class="form-control" id="trp-date" v-model="form.date" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-opening-km" class="col-sm-4 col-form-label">Opening KM</label>
              <div class="col-sm-5">
                <input type="number" class="form-control" id="trp-opening-km" placeholder="0.00" step="0.01" min="0" v-model="form.opening_km" @input="calcMileage" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-closing-km" class="col-sm-4 col-form-label">Closing KM</label>
              <div class="col-sm-5">
                <input type="number" class="form-control" id="trp-closing-km" placeholder="0.00" step="0.01" min="0" v-model="form.closing_km" @input="calcMileage" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-mileage" class="col-sm-4 col-form-label">Mileage</label>
              <div class="col-sm-5">
                <input type="number" class="form-control font-monospace" id="trp-mileage" v-model="form.mileage" disabled />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-run-no" class="col-sm-4 col-form-label">Run No.</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" id="trp-run-no" placeholder="Run number…" v-model="form.run_no" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-from" class="col-sm-4 col-form-label">From</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="trp-from" placeholder="Origin location…" v-model="form.from_loc" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-to" class="col-sm-4 col-form-label">To</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="trp-to" placeholder="Destination location…" v-model="form.to_loc" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-amount" class="col-sm-4 col-form-label">Amount</label>
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control" id="trp-amount" placeholder="0.00" step="0.01" min="0" v-model="form.amount" />
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-driver-salary" class="col-sm-4 col-form-label">Driver Salary</label>
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control" id="trp-driver-salary" placeholder="0.00" step="0.01" min="0" v-model="form.driver_salary" />
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-cleaner-salary" class="col-sm-4 col-form-label">Cleaner Salary</label>
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control" id="trp-cleaner-salary" placeholder="0.00" step="0.01" min="0" v-model="form.cleaner_salary" />
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-dept" class="col-sm-4 col-form-label">Department <span class="text-muted small">(opt.)</span></label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="trp-dept" placeholder="Department…" v-model="form.department" />
              </div>
            </div>

            <div class="row mb-3">
              <label for="trp-remark" class="col-sm-4 col-form-label">Remark <span class="text-muted small">(opt.)</span></label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="trp-remark" placeholder="Remarks…" v-model="form.remark" />
              </div>
            </div>

          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Trip saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/trip_running_chart_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/operations/trip_management/trip_running_chart/trip_running_chart.js"></script>

<style>
/* ── Journal Entry Preview ─────────────────────────────────────── */
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
  font-size: .7rem;
  font-weight: 600;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: #fff;
  background: #0d6efd;
  padding: 2px 9px;
  border-radius: 20px;
}

.je-preview__body    { padding: 12px 16px 14px; }
.je-preview__placeholder {
  padding: 14px 16px;
  font-size: .8rem;
  color: #adb5bd;
  font-style: italic;
}

.je-preview__table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: .81rem;
}
.je-preview__table thead tr {
  background: #f1f3f5;
}
.je-preview__table th {
  padding: 6px 10px;
  font-weight: 600;
  font-size: .72rem;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: #6c757d;
  border-bottom: 1.5px solid #dee2e6;
}
.je-preview__th--type   { width: 52px; }
.je-preview__th--code   { width: 64px; }
.je-preview__th--name   { }
.je-preview__th--amount { width: 130px; text-align: right; }

.je-preview__row td      { padding: 7px 10px; border-bottom: 1px solid #f1f3f5; }
.je-preview__row--dr td  { background: #fff9f9; }
.je-preview__row--cr td  { background: #f6fff8; }

.je-preview__type {
  display: inline-block;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .06em;
  padding: 2px 7px;
  border-radius: 4px;
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
  padding: 7px 10px;
  font-weight: 700;
  font-size: .8rem;
  background: #f8f9fa;
  border-top: 1.5px solid #dee2e6;
  font-family: monospace;
  text-align: right;
}
.je-preview__total td:first-child {
  text-align: left;
  font-family: inherit;
  color: #495057;
}
</style>
