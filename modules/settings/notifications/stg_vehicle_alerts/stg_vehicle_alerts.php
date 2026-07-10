<?php /* modules/settings/notifications/stg_vehicle_alerts/stg_vehicle_alerts.php — Vehicle Alerts rule matrix, included by home.php */ ?>

<div id="sva-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#svaSearchModal">Search</button>
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
              <label for="sva-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="sva-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-name" class="col-sm-4 col-form-label">Alert Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="sva-name" placeholder="e.g. License Expiry Warning" v-model="form.alert_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-condition" class="col-sm-4 col-form-label">Condition Field</label>
              <div class="col-sm-8">
                <input type="text" class="form-control font-monospace" id="sva-condition" list="sva-condition-options" placeholder="e.g. license_expiry_days" v-model="form.condition_field" />
                <datalist id="sva-condition-options">
                  <option value="license_expiry_days"></option>
                  <option value="insurance_expiry_days"></option>
                  <option value="revenue_license_expiry_days"></option>
                  <option value="emission_test_expiry_days"></option>
                  <option value="service_due_days"></option>
                  <option value="service_due_km"></option>
                  <option value="fuel_level_pct"></option>
                  <option value="odometer_reading_km"></option>
                  <option value="driver_license_expiry_days"></option>
                </datalist>
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-operator" class="col-sm-4 col-form-label">Operator</label>
              <div class="col-sm-4">
                <select class="form-select" id="sva-operator" v-model="form.operator">
                  <option value=">">&gt;</option>
                  <option value=">=">&gt;=</option>
                  <option value="<">&lt;</option>
                  <option value="<=">&lt;=</option>
                  <option value="=">=</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-description" class="col-sm-4 col-form-label">Description</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="sva-description" rows="3" placeholder="Optional notes" v-model="form.description"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="sva-threshold" class="col-sm-4 col-form-label">Threshold Value</label>
              <div class="col-sm-6">
                <input type="number" class="form-control text-end" id="sva-threshold" min="0" step="0.01" placeholder="0.00" v-model="form.threshold_value" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-channel" class="col-sm-4 col-form-label">Channel</label>
              <div class="col-sm-6">
                <select class="form-select" id="sva-channel" v-model="form.channel">
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="in_app">In-App</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-severity" class="col-sm-4 col-form-label">Severity</label>
              <div class="col-sm-5">
                <select class="form-select" id="sva-severity" v-model="form.severity">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Enabled</label>
              <div class="col-sm-8 d-flex align-items-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="sva-enabled" v-model="form.is_enabled" />
                  <label class="form-check-label" for="sva-enabled">Alert is active</label>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="sva-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="sva-status" v-model="form.status">
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
        <span class="text-success small" v-if="saved">Alert rule saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/stg_vehicle_alerts_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/settings/notifications/stg_vehicle_alerts/stg_vehicle_alerts.js"></script>
