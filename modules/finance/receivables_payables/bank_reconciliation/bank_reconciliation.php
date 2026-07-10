<?php /* modules/finance/bank_reconciliation/bank_reconciliation.php — Bank Reconciliation form, included by home.php */ ?>

<div id="bnk-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bnkSearchModal">Search</button>
            <button type="button" class="btn btn-secondary" @click="onEdit" :disabled="form.status === 'locked'">Edit</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button v-if="form.ref && form.status === 'draft'" type="button"
            class="btn btn-outline-success ms-2" @click="onReconcile"
            :disabled="Math.abs(difference) > 0.01">
            <i class="bi bi-check2-circle me-1"></i>Mark Reconciled
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <!-- Status banner -->
        <div v-if="form.status === 'reconciled'" class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
          <i class="bi bi-check-circle-fill"></i>
          <span>This reconciliation is <strong>reconciled</strong>.</span>
        </div>
        <div v-if="form.status === 'locked'" class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
          <i class="bi bi-lock-fill"></i>
          <span>This reconciliation is <strong>locked</strong> and cannot be edited.</span>
        </div>
        <div v-if="form.ref && Math.abs(difference) > 0.01" class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>Unreconciled difference: <strong>{{ differenceFmt }}</strong></span>
        </div>

        <div class="row mt-2 g-4">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="bnk-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="bnk-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="bnk-account-name" class="col-sm-4 col-form-label">Account Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="bnk-account-name" placeholder="e.g. Main Operating Account" v-model="form.account_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bnk-account-number" class="col-sm-4 col-form-label">Account Number</label>
              <div class="col-sm-7">
                <input type="text" class="form-control font-monospace" id="bnk-account-number" placeholder="Optional" v-model="form.account_number" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bnk-period" class="col-sm-4 col-form-label">Period</label>
              <div class="col-sm-5">
                <input type="month" class="form-control" id="bnk-period" v-model="form.period" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bnk-recon-date" class="col-sm-4 col-form-label">Reconciliation Date</label>
              <div class="col-sm-6">
                <input type="date" class="form-control" id="bnk-recon-date" v-model="form.reconciliation_date" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bnk-notes" class="col-sm-4 col-form-label">Notes</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="bnk-notes" rows="3" placeholder="Optional notes" v-model="form.notes"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column — balances -->
          <div class="col-md-6">
            <div class="card bg-light border-0 p-3 mb-3">
              <div class="fw-semibold small text-uppercase text-muted mb-3">Balance Summary</div>

              <div class="row mb-2">
                <label class="col-sm-7 col-form-label col-form-label-sm">Bank Statement Balance</label>
                <div class="col-sm-5">
                  <input type="number" class="form-control form-control-sm text-end" min="0" step="0.01" v-model="form.bank_balance" />
                </div>
              </div>
              <div class="row mb-2">
                <label class="col-sm-7 col-form-label col-form-label-sm">+ Outstanding Deposits</label>
                <div class="col-sm-5">
                  <input type="number" class="form-control form-control-sm text-end" min="0" step="0.01" v-model="form.outstanding_deposits" />
                </div>
              </div>
              <div class="row mb-2">
                <label class="col-sm-7 col-form-label col-form-label-sm">− Outstanding Cheques</label>
                <div class="col-sm-5">
                  <input type="number" class="form-control form-control-sm text-end" min="0" step="0.01" v-model="form.outstanding_cheques" />
                </div>
              </div>
              <div class="row mb-3">
                <label class="col-sm-7 col-form-label col-form-label-sm fw-semibold">Adjusted Bank Balance</label>
                <div class="col-sm-5">
                  <input type="text" class="form-control form-control-sm text-end fw-semibold font-monospace bg-white" :value="adjustedBankFmt" disabled />
                </div>
              </div>

              <hr class="my-2" />

              <div class="row mb-2">
                <label class="col-sm-7 col-form-label col-form-label-sm">Book Balance</label>
                <div class="col-sm-5">
                  <input type="number" class="form-control form-control-sm text-end" min="0" step="0.01" v-model="form.book_balance" />
                </div>
              </div>
              <div class="row">
                <label class="col-sm-7 col-form-label col-form-label-sm fw-semibold">Difference</label>
                <div class="col-sm-5">
                  <input type="text" class="form-control form-control-sm text-end fw-semibold font-monospace bg-white"
                    :class="Math.abs(difference) <= 0.01 ? 'text-success' : 'text-danger'"
                    :value="differenceFmt" disabled />
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label for="bnk-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="bnk-status" v-model="form.status" :disabled="form.status === 'locked'">
                  <option value="draft">Draft</option>
                  <option value="reconciled">Reconciled</option>
                  <option value="locked">Locked</option>
                </select>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Reconciliation saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave"
          :disabled="!isDirty || saving || form.status === 'locked'">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/bank_reconciliation_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/receivables_payables/bank_reconciliation/bank_reconciliation.js"></script>
