<?php /* modules/finance/chart_of_accounts/chart_of_accounts.php — Chart of Accounts form, included by home.php */ ?>

<div id="coa-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#coaSearchModal">Search</button>
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
              <label for="coa-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="coa-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="coa-code" class="col-sm-4 col-form-label">Account Code</label>
              <div class="col-sm-5">
                <input type="text" class="form-control font-monospace" id="coa-code" placeholder="e.g. 1000" v-model="form.account_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="coa-name" class="col-sm-4 col-form-label">Account Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="coa-name" placeholder="e.g. Cash and Cash Equivalents" v-model="form.account_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="coa-type" class="col-sm-4 col-form-label">Account Type</label>
              <div class="col-sm-6">
                <select class="form-select" id="coa-type" v-model="form.account_type">
                  <option value="">— Select —</option>
                  <option value="Asset">Asset</option>
                  <option value="Liability">Liability</option>
                  <option value="Equity">Equity</option>
                  <option value="Revenue">Revenue</option>
                  <option value="Expense">Expense</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="coa-subtype" class="col-sm-4 col-form-label">Sub-Type</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="coa-subtype" placeholder="e.g. Current Asset" v-model="form.account_sub_type" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="coa-parent" class="col-sm-4 col-form-label">Parent Code</label>
              <div class="col-sm-5">
                <input type="text" class="form-control font-monospace" id="coa-parent" placeholder="e.g. 1000" v-model="form.parent_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="coa-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="coa-status" v-model="form.status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="coa-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="coa-description" rows="5" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Account saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/chart_of_accounts_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/accounting/chart_of_accounts/chart_of_accounts.js"></script>
