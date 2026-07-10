<?php /* entries/vehicle_master_file/vehicle_master_file.php — Vehicle form, included by home.php */ ?>

<div id="vehicle-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#vehicleSearchModal">Search</button>
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
              <label for="vh-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="vh-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-plate" class="col-sm-4 col-form-label">Plate Number</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="vh-plate" placeholder="e.g. ABC-1234" v-model="form.plate_number" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-make" class="col-sm-4 col-form-label">Make</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="vh-make" placeholder="e.g. Toyota" v-model="form.make" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-model" class="col-sm-4 col-form-label">Model</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="vh-model" placeholder="e.g. Dyna" v-model="form.model" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-year" class="col-sm-4 col-form-label">Year</label>
              <div class="col-sm-4">
                <input type="number" class="form-control" id="vh-year" placeholder="e.g. 2020" v-model="form.year" min="1900" max="2100" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-capacity" class="col-sm-4 col-form-label">Capacity</label>
              <div class="col-sm-4">
                <input type="number" class="form-control" id="vh-capacity" placeholder="0" v-model="form.capacity" min="0" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="vh-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="vh-status" v-model="form.status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-type" class="col-sm-4 col-form-label">Type</label>
              <div class="col-sm-7">
                <select class="form-select" id="vh-type" v-model="form.type">
                  <option value="">— Select —</option>
                  <option value="lorry">Lorry</option>
                  <option value="bowser">Bowser</option>
                  <option value="tipper">Tipper</option>
                  <option value="truck">Truck</option>
                  <option value="van">Van</option>
                  <option value="bus">Bus</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-fuel" class="col-sm-4 col-form-label">Fuel Type</label>
              <div class="col-sm-6">
                <select class="form-select" id="vh-fuel" v-model="form.fuel_type">
                  <option value="">— Select —</option>
                  <option value="petrol">Petrol</option>
                  <option value="diesel">Diesel</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-mileage" class="col-sm-4 col-form-label">Mileage</label>
              <div class="col-sm-5">
                <input type="number" class="form-control" id="vh-mileage" placeholder="0" v-model="form.mileage" min="0" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="vh-location" class="col-sm-4 col-form-label">Last Location</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="vh-location" placeholder="e.g. Depot" v-model="form.last_location" />
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Vehicle saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/vehicle_master_file_search.php'; ?>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file.js"></script>
