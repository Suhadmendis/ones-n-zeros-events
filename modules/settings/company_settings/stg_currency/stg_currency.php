<?php /* modules/settings/company_settings/stg_currency/stg_currency.php — Currency form, included by home.php */ ?>

<div id="stgcur-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#stgCurSearchModal">Search</button>
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
              <label for="stgcur-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="stgcur-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="stgcur-code" class="col-sm-4 col-form-label">Currency Code</label>
              <div class="col-sm-5">
                <input type="text" class="form-control font-monospace text-uppercase" id="stgcur-code" placeholder="e.g. USD" maxlength="10" v-model="form.currency_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="stgcur-name" class="col-sm-4 col-form-label">Currency Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="stgcur-name" placeholder="e.g. US Dollar" v-model="form.currency_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="stgcur-symbol" class="col-sm-4 col-form-label">Symbol</label>
              <div class="col-sm-4">
                <input type="text" class="form-control" id="stgcur-symbol" placeholder="e.g. $" maxlength="10" v-model="form.symbol" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="stgcur-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="stgcur-description" rows="3" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="stgcur-rate" class="col-sm-4 col-form-label">Exchange Rate</label>
              <div class="col-sm-6">
                <input type="number" class="form-control text-end" id="stgcur-rate" min="0" step="0.000001" placeholder="1.000000" v-model="form.exchange_rate" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="stgcur-effective" class="col-sm-4 col-form-label">Effective Date</label>
              <div class="col-sm-6">
                <input type="date" class="form-control" id="stgcur-effective" v-model="form.effective_date" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Base Currency</label>
              <div class="col-sm-8 d-flex align-items-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="stgcur-base" v-model="form.is_base_currency" />
                  <label class="form-check-label" for="stgcur-base">Mark as base currency</label>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="stgcur-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="stgcur-status" v-model="form.status">
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
        <span class="text-success small" v-if="saved">Currency saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/stg_currency_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/settings/company_settings/stg_currency/stg_currency.js"></script>
