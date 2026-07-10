<?php
// deduction.php — included by home.php, $page is available
?>
<div id="deduction-app" v-cloak>
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="card-title mb-0">{{ title }}</h5>
      <div class="d-flex gap-2 align-items-center">
        <button class="btn btn-sm btn-secondary" @click="searchRecord">
          <i class="bi bi-search"></i> Search
        </button>
        <button class="btn btn-sm btn-primary" @click="newRecord">
          <i class="bi bi-plus-lg"></i> New
        </button>
        <div class="form-check form-switch d-flex align-items-center gap-2 ms-2 mb-0" v-show="financeEnabled">
          <input class="form-check-input" type="checkbox" role="switch" id="checkGLToggle" v-model="checkGL" style="width:2.4em;height:1.25em;cursor:pointer">
          <label class="form-check-label fw-semibold text-nowrap" for="checkGLToggle" style="cursor:pointer;font-size:.85rem">Check GL</label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary module-help-btn" title="Help">
          <i class="bi bi-question-circle"></i> Help
        </button>
      </div>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <span class="ms-2">Loading…</span>
      </div>
      <div v-if="error" class="alert alert-danger">{{ error }}</div>
      <div v-if="success" class="alert alert-success">{{ success }}</div>

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
              <template v-if="form.recipient_type === 'driver'">
                <tr class="je-preview__row je-preview__row--dr">
                  <td><span class="je-preview__type je-preview__type--dr">DR</span></td>
                  <td class="je-preview__code">2200</td>
                  <td class="je-preview__name">Driver Salaries Payable</td>
                  <td class="je-preview__amount je-preview__amount--dr">{{ fmtAmount(jeTotalAmount) }}</td>
                  <td class="je-preview__amount je-preview__amount--blank">—</td>
                </tr>
                <tr class="je-preview__row je-preview__row--cr">
                  <td><span class="je-preview__type je-preview__type--cr">CR</span></td>
                  <td class="je-preview__code">1210</td>
                  <td class="je-preview__name">Staff Advances Receivable</td>
                  <td class="je-preview__amount je-preview__amount--blank">—</td>
                  <td class="je-preview__amount je-preview__amount--cr">{{ fmtAmount(jeTotalAmount) }}</td>
                </tr>
              </template>
              <template v-else>
                <tr class="je-preview__row je-preview__row--dr">
                  <td><span class="je-preview__type je-preview__type--dr">DR</span></td>
                  <td class="je-preview__code">2210</td>
                  <td class="je-preview__name">Cleaner Salaries Payable</td>
                  <td class="je-preview__amount je-preview__amount--dr">{{ fmtAmount(jeTotalAmount) }}</td>
                  <td class="je-preview__amount je-preview__amount--blank">—</td>
                </tr>
                <tr class="je-preview__row je-preview__row--cr">
                  <td><span class="je-preview__type je-preview__type--cr">CR</span></td>
                  <td class="je-preview__code">1210</td>
                  <td class="je-preview__name">Staff Advances Receivable</td>
                  <td class="je-preview__amount je-preview__amount--blank">—</td>
                  <td class="je-preview__amount je-preview__amount--cr">{{ fmtAmount(jeTotalAmount) }}</td>
                </tr>
              </template>
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

      <form @submit.prevent="saveRecord" novalidate>
        <div class="row g-3">

          <!-- Reference No -->
          <div class="col-sm-3">
            <label class="form-label">Reference No.</label>
            <input type="text" class="form-control font-monospace" :value="form.ref" disabled />
          </div>

          <!-- Date -->
          <div class="col-sm-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" v-model="form.date" required />
          </div>

          <!-- Spacer -->
          <div class="col-sm-6"></div>

          <!-- Recipient Type -->
          <div class="col-sm-3">
            <label class="form-label">Recipient Type <span class="text-danger">*</span></label>
            <select class="form-select" v-model="form.recipient_type" required>
              <option value="">— Select —</option>
              <option value="driver">Driver</option>
              <option value="cleaner">Cleaner</option>
            </select>
          </div>

          <!-- Driver Picker (visible when recipient_type === 'driver') -->
          <template v-if="form.recipient_type === 'driver'">
            <div class="col-sm-6">
              <label class="form-label">Driver</label>
              <div class="input-group">
                <input type="text" class="form-control font-monospace" v-model="form.recipient_ref" readonly placeholder="Ref…" style="max-width:150px" />
                <input type="text" class="form-control" v-model="form.recipient_name" readonly placeholder="Driver name…" />
                <button type="button" class="btn btn-outline-secondary" @click="openDriverPicker">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </div>
          </template>

          <!-- Cleaner Picker (visible when recipient_type === 'cleaner') -->
          <template v-if="form.recipient_type === 'cleaner'">
            <div class="col-sm-6">
              <label class="form-label">Cleaner</label>
              <div class="input-group">
                <input type="text" class="form-control font-monospace" v-model="form.recipient_ref" readonly placeholder="Ref…" style="max-width:150px" />
                <input type="text" class="form-control" v-model="form.recipient_name" readonly placeholder="Cleaner name…" />
                <button type="button" class="btn btn-outline-secondary" @click="openCleanerPicker">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </div>
          </template>

          <!-- Amount -->
          <div class="col-sm-3">
            <label class="form-label">Amount (LKR) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">LKR</span>
              <input type="number" class="form-control" v-model="form.amount" step="0.01" min="0" placeholder="0.00" required />
            </div>
          </div>

          <!-- Reason -->
          <div class="col-sm-6">
            <label class="form-label">Reason <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="form.reason" placeholder="Reason for deduction…" required />
          </div>

        </div><!-- row -->

        <div class="d-flex gap-2 mt-4">
          <button type="button" class="btn btn-outline-secondary" @click="newRecord">
            <i class="bi bi-x-circle"></i> Clear
          </button>
          <button type="button" class="btn btn-outline-primary ms-auto" @click="printRecord" v-if="form.id">
            <i class="bi bi-printer"></i> Print
          </button>
        </div>
      </form>

    </div><!-- card-body -->
    <div class="card-footer d-flex align-items-center">
      <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="saveRecord" :disabled="saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
        <i v-else class="bi bi-check-lg me-2"></i>Save
      </button>
    </div>
  </div><!-- card -->
</div><!-- #deduction-app -->
<script src="/modules/operations/staff_payroll/deduction/deduction.js"></script>

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
