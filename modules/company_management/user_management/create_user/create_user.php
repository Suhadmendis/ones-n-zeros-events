<?php /* entries/create_user/create_user.php — User form, included by home.php */ ?>

<div id="user-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userSearchModal">Search</button>
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
              <label for="us-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="us-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="us-full-name" class="col-sm-4 col-form-label">Full Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="us-full-name" placeholder="Full name" v-model="form.full_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="us-email" class="col-sm-4 col-form-label">Email</label>
              <div class="col-sm-8">
                <input type="email" class="form-control" id="us-email" placeholder="user@example.com" v-model="form.email" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="us-password" class="col-sm-4 col-form-label">Password</label>
              <div class="col-sm-8">
                <input type="password" class="form-control" id="us-password" placeholder="Set password" v-model="form.password" autocomplete="new-password" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Roles</label>
              <div class="col-sm-8">
                <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
                  <div class="form-check" v-for="role in roles" :key="role.ref">
                    <input class="form-check-input" type="checkbox" :id="'us-role-' + role.ref" :value="role.ref" v-model="form.role_refs" />
                    <label class="form-check-label" :for="'us-role-' + role.ref">{{ role.name }}</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="us-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="us-status" v-model="form.record_status">
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
        <span class="text-success small" v-if="saved">User saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/create_user_search.php'; ?>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/company_management/user_management/create_user/create_user.js"></script>
