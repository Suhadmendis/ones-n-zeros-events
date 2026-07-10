<?php /* modules/settings/number_series/series_overview/series_overview.php — Series Overview master file */ ?>

<div id="nsr-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">Series Overview</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#nsrSearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <p class="text-muted small" v-if="!form.id">Select a module's number series via <strong>Search</strong> to view or edit it. Each row is provisioned per module — new series can't be created here.</p>

        <div class="row mt-2 g-4" v-if="form.id">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="nsr-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <input type="text" class="form-control font-monospace" id="nsr-ref" v-model="form.ref" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="nsr-module" class="col-sm-4 col-form-label">Module</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="nsr-module" v-model="form.module_name" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="nsr-prefix" class="col-sm-4 col-form-label">Prefix</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="nsr-prefix" placeholder="e.g. DRV" v-model="form.prefix" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="nsr-suffix" class="col-sm-4 col-form-label">Suffix</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="nsr-suffix" placeholder="Optional suffix" v-model="form.suffix" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="nsr-separator" class="col-sm-4 col-form-label">Separator</label>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="nsr-separator" maxlength="3" v-model="form.separator" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="nsr-padding" class="col-sm-4 col-form-label">Padding</label>
              <div class="col-sm-5">
                <input type="number" class="form-control" id="nsr-padding" min="1" max="10" v-model.number="form.padding_length" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="nsr-reset" class="col-sm-4 col-form-label">Reset Period</label>
              <div class="col-sm-8">
                <select class="form-select" id="nsr-reset" v-model="form.reset_period">
                  <option value="never">Never</option>
                  <option value="daily">Daily</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Current Number</label>
              <div class="col-sm-8 d-flex align-items-center">
                <span class="font-monospace">{{ form.current_number }}</span>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Next Preview</label>
              <div class="col-sm-8 d-flex align-items-center">
                <span class="font-monospace text-primary fw-bold">{{ preview }}</span>
              </div>
            </div>
            <div class="row">
              <label class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-8 d-flex align-items-center">
                <span class="badge" :class="form.record_status === 'active' ? 'text-bg-success' : 'text-bg-secondary'">{{ form.record_status }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Number series saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!form.id || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/series_overview_search.php'; ?>

<script src="/modules/settings/number_series/series_overview/series_overview.js"></script>
