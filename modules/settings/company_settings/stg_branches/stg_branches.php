<?php /* modules/settings/company_settings/stg_branches/stg_branches.php — Branch form, included by home.php */ ?>

<div id="br-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#brSearchModal">Search</button>
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
              <label for="br-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="br-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-name" class="col-sm-4 col-form-label">Branch Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="br-name" placeholder="e.g. Colombo Main Branch" v-model="form.branch_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-code" class="col-sm-4 col-form-label">Branch Code</label>
              <div class="col-sm-5">
                <input type="text" class="form-control font-monospace text-uppercase" id="br-code" placeholder="e.g. CMB01" maxlength="20" v-model="form.branch_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-address" class="col-sm-4 col-form-label">Address</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="br-address" rows="3" placeholder="Street address" v-model="form.address"></textarea>
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-city" class="col-sm-4 col-form-label">City</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="br-city" placeholder="e.g. Colombo" v-model="form.city" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="br-phone" class="col-sm-4 col-form-label">Phone</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="br-phone" placeholder="e.g. 0112345678" v-model="form.phone" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-email" class="col-sm-4 col-form-label">Email</label>
              <div class="col-sm-8">
                <input type="email" class="form-control" id="br-email" placeholder="branch@lankatransport.lk" v-model="form.email" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-manager" class="col-sm-4 col-form-label">Manager Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="br-manager" placeholder="Branch manager" v-model="form.manager_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Head Office</label>
              <div class="col-sm-8 d-flex align-items-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="br-head-office" v-model="form.is_head_office" />
                  <label class="form-check-label" for="br-head-office">Mark as head office</label>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="br-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="br-status" v-model="form.status">
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
        <span class="text-success small" v-if="saved">Branch saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/stg_branches_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/settings/company_settings/stg_branches/stg_branches.js"></script>
