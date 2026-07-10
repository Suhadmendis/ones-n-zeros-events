<?php /* entries/driver_master_file/driver_master_file.php — Driver form, included by home.php */ ?>

<div id="driver-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#driverSearchModal">Search</button>
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
              <label for="dr-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="dr-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="dr-name" class="col-sm-4 col-form-label">Full Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="dr-name" placeholder="Driver's full name" v-model="form.name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="dr-license" class="col-sm-4 col-form-label">License No.</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="dr-license" placeholder="License number" v-model="form.license_number" />
              </div>
            </div>
            <div class="row">
              <label class="col-sm-4 col-form-label">Employee</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="dr-employee-ref" v-model="form.employee_ref" placeholder="Ref…" readonly style="max-width:110px" />
                  <input type="text" class="form-control" v-model="form.employee_name" placeholder="Optional — link to employee master" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#driverEmployeePickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearEmployee" v-if="form.employee_ref">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="dr-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="dr-status" v-model="form.status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="on_leave">On Leave</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="dr-phone" class="col-sm-4 col-form-label">Phone</label>
              <div class="col-sm-7">
                <input type="tel" class="form-control" id="dr-phone" placeholder="Phone number" v-model="form.phone" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="dr-dob" class="col-sm-4 col-form-label">Date of Birth</label>
              <div class="col-sm-7">
                <input type="date" class="form-control" id="dr-dob" v-model="form.date_of_birth" />
              </div>
            </div>
            <div class="row">
              <label for="dr-join" class="col-sm-4 col-form-label">Joining Date</label>
              <div class="col-sm-7">
                <input type="date" class="form-control" id="dr-join" v-model="form.joining_date" />
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Driver saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/driver_master_file_search.php'; ?>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/master_files/staff_fleet/driver_master_file/driver_master_file.js"></script>
