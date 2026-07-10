<?php /* modules/finance/budgeting/budgeting.php — Budgeting form, included by home.php */ ?>

<div id="bdg-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bdgSearchModal">Search</button>
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
              <label for="bdg-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="bdg-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-name" class="col-sm-4 col-form-label">Budget Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="bdg-name" placeholder="e.g. Q1 2026 Operating Budget" v-model="form.budget_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-account" class="col-sm-4 col-form-label">Account Code</label>
              <div class="col-sm-5">
                <input type="text" class="form-control font-monospace" id="bdg-account" placeholder="e.g. 5000" v-model="form.account_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-period" class="col-sm-4 col-form-label">Period</label>
              <div class="col-sm-5">
                <input type="month" class="form-control" id="bdg-period" v-model="form.period" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-notes" class="col-sm-4 col-form-label">Notes</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="bdg-notes" rows="3" placeholder="Optional notes" v-model="form.notes"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="bdg-category" class="col-sm-4 col-form-label">Category</label>
              <div class="col-sm-6">
                <select class="form-select" id="bdg-category" v-model="form.category">
                  <option value="">— Select —</option>
                  <option value="Revenue">Revenue</option>
                  <option value="Expense">Expense</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-type" class="col-sm-4 col-form-label">Budget Type</label>
              <div class="col-sm-6">
                <select class="form-select" id="bdg-type" v-model="form.budget_type">
                  <option value="Monthly">Monthly</option>
                  <option value="Quarterly">Quarterly</option>
                  <option value="Annual">Annual</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-budget-amount" class="col-sm-4 col-form-label">Budget Amount</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control text-end" id="bdg-budget-amount" min="0" step="0.01" placeholder="0.00" v-model="form.budget_amount" />
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-actual-amount" class="col-sm-4 col-form-label">Actual Amount</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control text-end" id="bdg-actual-amount" min="0" step="0.01" placeholder="0.00" v-model="form.actual_amount" />
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Variance</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="text" class="form-control text-end font-monospace" :value="fmtNum(variance)" disabled :class="variance >= 0 ? 'text-success' : 'text-danger'" />
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="bdg-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="bdg-status" v-model="form.status">
                  <option value="draft">Draft</option>
                  <option value="approved">Approved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Budget saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/budgeting_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/assets_budgeting/budgeting/budgeting.js"></script>
