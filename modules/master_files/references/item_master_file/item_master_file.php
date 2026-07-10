<?php /* entries/item_master_file/item_master_file.php — Item form, included by home.php */ ?>

<div id="item-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#itemSearchModal">Search</button>
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
              <label for="it-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="it-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="it-name" class="col-sm-4 col-form-label">Item Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="it-name" placeholder="Item name" v-model="form.name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="it-category" class="col-sm-4 col-form-label">Category</label>
              <div class="col-sm-7">
                <select class="form-select" id="it-category" v-model="form.category">
                  <option value="">— Select —</option>
                  <option value="fuel">Fuel</option>
                  <option value="equipment">Equipment</option>
                  <option value="materials">Materials</option>
                  <option value="cargo">Cargo</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="it-unit" class="col-sm-4 col-form-label">Unit</label>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="it-unit" placeholder="e.g. kg, L, pcs" v-model="form.unit" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="it-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="it-status" v-model="form.status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="it-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="it-description" rows="4" placeholder="Optional description" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Item saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/item_master_file_search.php'; ?>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/master_files/references/item_master_file/item_master_file.js"></script>
