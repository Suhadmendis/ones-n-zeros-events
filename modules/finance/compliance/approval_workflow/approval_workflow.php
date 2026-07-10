<?php /* modules/finance/approval_workflow/approval_workflow.php — Approval Workflow form, included by home.php */ ?>

<div id="apw-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#apwSearchModal">Search</button>
            <button type="button" class="btn btn-secondary" @click="onEdit">Edit</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="row mt-2 g-4">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="apw-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="apw-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-name" class="col-sm-4 col-form-label">Workflow Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="apw-name" placeholder="e.g. Journal Approval" v-model="form.workflow_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-module" class="col-sm-4 col-form-label">Module</label>
              <div class="col-sm-8">
                <select class="form-select" id="apw-module" v-model="form.module">
                  <option value="">— Select —</option>
                  <option value="journal_entries">Journal Entries</option>
                  <option value="accounts_payable">Accounts Payable</option>
                  <option value="accounts_receivable">Accounts Receivable</option>
                  <option value="budgeting">Budgeting</option>
                  <option value="fixed_assets">Fixed Assets</option>
                  <option value="cash_bank">Cash &amp; Bank</option>
                  <option value="period_closing">Period Closing</option>
                  <option value="bank_reconciliation">Bank Reconciliation</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-approver" class="col-sm-4 col-form-label">Approver Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="apw-approver" placeholder="Full name" v-model="form.approver_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-approver-ref" class="col-sm-4 col-form-label">Approver Ref</label>
              <div class="col-sm-6">
                <input type="text" class="form-control font-monospace" id="apw-approver-ref" placeholder="User ref (optional)" v-model="form.approver_ref" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="apw-description" rows="3" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="apw-order" class="col-sm-4 col-form-label">Approval Order</label>
              <div class="col-sm-4">
                <input type="number" class="form-control text-center" id="apw-order" min="1" step="1" v-model="form.approval_order" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-min" class="col-sm-4 col-form-label">Min Amount</label>
              <div class="col-sm-6">
                <input type="number" class="form-control text-end" id="apw-min" min="0" step="0.01" placeholder="0.00 (optional)" v-model="form.min_amount" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-max" class="col-sm-4 col-form-label">Max Amount</label>
              <div class="col-sm-6">
                <input type="number" class="form-control text-end" id="apw-max" min="0" step="0.01" placeholder="0.00 (optional)" v-model="form.max_amount" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Mandatory</label>
              <div class="col-sm-8 d-flex align-items-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="apw-mandatory" v-model="form.is_mandatory" />
                  <label class="form-check-label" for="apw-mandatory">Required for approval</label>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="apw-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="apw-status" v-model="form.status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Workflow saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/approval_workflow_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/compliance/approval_workflow/approval_workflow.js"></script>
