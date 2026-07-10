<?php /* modules/finance/tax_management/tax_management.php — Tax Management form, included by home.php */ ?>

<div id="tax-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#taxSearchModal">Search</button>
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
              <label for="tax-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="tax-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-name" class="col-sm-4 col-form-label">Tax Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="tax-name" placeholder="e.g. Value Added Tax" v-model="form.tax_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-code" class="col-sm-4 col-form-label">Tax Code</label>
              <div class="col-sm-5">
                <input type="text" class="form-control font-monospace" id="tax-code" placeholder="e.g. VAT-18" v-model="form.tax_code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-type" class="col-sm-4 col-form-label">Tax Type</label>
              <div class="col-sm-8">
                <select class="form-select" id="tax-type" v-model="form.tax_type">
                  <option value="">— Select —</option>
                  <option value="VAT">VAT</option>
                  <option value="Income Tax">Income Tax</option>
                  <option value="Withholding Tax">Withholding Tax</option>
                  <option value="Customs Duty">Customs Duty</option>
                  <option value="Excise Duty">Excise Duty</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="tax-description" rows="3" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="tax-rate" class="col-sm-4 col-form-label">Rate (%)</label>
              <div class="col-sm-5">
                <div class="input-group">
                  <input type="number" class="form-control text-end" id="tax-rate" min="0" max="100" step="0.0001" placeholder="0.00" v-model="form.rate" />
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-applicable" class="col-sm-4 col-form-label">Applicable On</label>
              <div class="col-sm-6">
                <select class="form-select" id="tax-applicable" v-model="form.applicable_on">
                  <option value="Both">Both</option>
                  <option value="Revenue">Revenue</option>
                  <option value="Expense">Expense</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-effective-date" class="col-sm-4 col-form-label">Effective Date</label>
              <div class="col-sm-6">
                <input type="date" class="form-control" id="tax-effective-date" v-model="form.effective_date" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-expiry-date" class="col-sm-4 col-form-label">Expiry Date</label>
              <div class="col-sm-6">
                <input type="date" class="form-control" id="tax-expiry-date" v-model="form.expiry_date" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="tax-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="tax-status" v-model="form.status">
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
        <span class="text-success small" v-if="saved">Tax record saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/tax_management_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/compliance/tax_management/tax_management.js"></script>
