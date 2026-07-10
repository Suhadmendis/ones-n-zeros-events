<?php /* entries/location_master_file/location_master_file.php — Location form, included by home.php */ ?>

<div id="location-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#locationSearchModal">Search</button>
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
              <label for="loc-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="loc-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="loc-name" class="col-sm-4 col-form-label">Location Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="loc-name" placeholder="Location name" v-model="form.name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="loc-district" class="col-sm-4 col-form-label">District</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="loc-district" placeholder="District" v-model="form.district" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="loc-province" class="col-sm-4 col-form-label">Province</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="loc-province" placeholder="Province" v-model="form.province" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="loc-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="loc-status" v-model="form.status">
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
        <span class="text-success small" v-if="saved">Location saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/location_master_file_search.php'; ?>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/master_files/references/location_master_file/location_master_file.js"></script>
