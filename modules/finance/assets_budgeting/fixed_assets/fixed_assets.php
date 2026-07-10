<?php /* modules/finance/fixed_assets/fixed_assets.php — Fixed Assets form, included by home.php */ ?>

<div id="fa-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#faSearchModal">Search</button>
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
              <label for="fa-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="fa-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-name" class="col-sm-4 col-form-label">Asset Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="fa-name" placeholder="e.g. Office Building" v-model="form.asset_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-category" class="col-sm-4 col-form-label">Category</label>
              <div class="col-sm-7">
                <input type="text" class="form-control" id="fa-category" placeholder="e.g. Land, Vehicle, Equipment" v-model="form.asset_category" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-location" class="col-sm-4 col-form-label">Location</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="fa-location" placeholder="Optional" v-model="form.location" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="fa-description" rows="3" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="fa-status" v-model="form.status">
                  <option value="active">Active</option>
                  <option value="disposed">Disposed</option>
                  <option value="fully_depreciated">Fully Depreciated</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="fa-purchase-date" class="col-sm-4 col-form-label">Purchase Date</label>
              <div class="col-sm-6">
                <input type="date" class="form-control" id="fa-purchase-date" v-model="form.purchase_date" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-purchase-cost" class="col-sm-4 col-form-label">Purchase Cost</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control text-end" id="fa-purchase-cost" min="0" step="0.01" placeholder="0.00" v-model="form.purchase_cost" />
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-salvage" class="col-sm-4 col-form-label">Salvage Value</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control text-end" id="fa-salvage" min="0" step="0.01" placeholder="0.00" v-model="form.salvage_value" />
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-useful-life" class="col-sm-4 col-form-label">Useful Life (yrs)</label>
              <div class="col-sm-4">
                <input type="number" class="form-control text-end" id="fa-useful-life" min="1" step="1" placeholder="1" v-model="form.useful_life_years" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-dep-method" class="col-sm-4 col-form-label">Depreciation</label>
              <div class="col-sm-8">
                <select class="form-select" id="fa-dep-method" v-model="form.depreciation_method">
                  <option value="Straight Line">Straight Line</option>
                  <option value="Declining Balance">Declining Balance</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="fa-acc-dep" class="col-sm-4 col-form-label">Accum. Depreciation</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="number" class="form-control text-end" id="fa-acc-dep" min="0" step="0.01" placeholder="0.00" v-model="form.accumulated_depreciation" />
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Net Book Value</label>
              <div class="col-sm-7">
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="text" class="form-control text-end font-monospace" :value="fmtNum(netBookValue)" disabled />
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Asset saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/fixed_assets_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/assets_budgeting/fixed_assets/fixed_assets.js"></script>
