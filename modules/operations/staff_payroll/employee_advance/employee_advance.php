<?php /* entries/driver_advance/driver_advance.php — Driver advance form, included by home.php */ ?>

<div id="adv-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#advSearchModal">Search</button>
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
                  <td class="je-preview__code">1210</td>
                  <td class="je-preview__name">Staff Advances Receivable</td>
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

        <div class="row mt-2 g-4">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="adv-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="adv-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Recipient Type</label>
              <div class="col-sm-8 d-flex align-items-center gap-4">
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="adv-type-driver" value="driver" v-model="form.recipient_type" @change="onTypeChange" />
                  <label class="form-check-label" for="adv-type-driver">Driver</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="adv-type-cleaner" value="cleaner" v-model="form.recipient_type" @change="onTypeChange" />
                  <label class="form-check-label" for="adv-type-cleaner">Cleaner</label>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="adv-recipient" class="col-sm-4 col-form-label">
                {{ form.recipient_type === 'driver' ? 'Driver' : 'Cleaner' }}
              </label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control" id="adv-recipient" v-model="form.recipient_display" placeholder="Select recipient…" readonly />
                  <button class="btn btn-outline-secondary" type="button"
                    :data-bs-target="form.recipient_type === 'driver' ? '#advDriverPickerModal' : '#advCleanerPickerModal'"
                    data-bs-toggle="modal">
                    <i class="bi bi-search"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="adv-date" class="col-sm-4 col-form-label">Date</label>
              <div class="col-sm-5">
                <input type="date" class="form-control" id="adv-date" v-model="form.date" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="adv-amount" class="col-sm-4 col-form-label">Amount</label>
              <div class="col-sm-6">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control" id="adv-amount" placeholder="0.00" step="0.01" min="0" v-model="form.amount" />
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Advance payment saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/driver_advance_search.php'; ?>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/operations/staff_payroll/driver_advance/driver_advance.js"></script>

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
