<?php /* modules/finance/cost_centers/cost_centers.php — Cost Centers form, included by home.php */ ?>

<div id="cst-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#cstSearchModal">Search</button>
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
              <label for="cst-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="cst-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-code" class="col-sm-4 col-form-label">Cost Center Code</label>
              <div class="col-sm-6">
                <input type="text" class="form-control font-monospace" id="cst-code" placeholder="e.g. CC-001" v-model="form.cost_center_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-name" class="col-sm-4 col-form-label">Cost Center Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cst-name" placeholder="e.g. Operations" v-model="form.cost_center_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-dept" class="col-sm-4 col-form-label">Department</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cst-dept" placeholder="Optional" v-model="form.department" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-manager" class="col-sm-4 col-form-label">Manager</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cst-manager" placeholder="Optional" v-model="form.manager" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="cst-description" rows="3" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="cst-budget" class="col-sm-4 col-form-label">Budget Amount</label>
              <div class="col-sm-7">
                <input type="number" class="form-control text-end" id="cst-budget" min="0" step="0.01" placeholder="0.00" v-model="form.budget_amount" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-actual" class="col-sm-4 col-form-label">Actual Amount</label>
              <div class="col-sm-7">
                <input type="number" class="form-control text-end" id="cst-actual" min="0" step="0.01" placeholder="0.00" v-model="form.actual_amount" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Variance</label>
              <div class="col-sm-7">
                <input type="text" class="form-control text-end font-monospace"
                  :class="variance >= 0 ? 'text-success' : 'text-danger'"
                  :value="varianceFmt" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cst-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="cst-status" v-model="form.status">
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
        <span class="text-success small" v-if="saved">Cost center saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/cost_centers_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/assets_budgeting/cost_centers/cost_centers.js"></script>
