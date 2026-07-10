<?php /* entries/edit_deactivate_user/edit_deactivate_user.php — Edit / deactivate user form */ ?>

<div id="edit-user-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editUserSearchModal">Search</button>
            <button type="button" class="btn btn-warning" @click="onCancel" :disabled="!userLoaded">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" :disabled="!userLoaded">Options</button>
            <ul class="dropdown-menu">
              <li>
                <a class="dropdown-item text-danger" href="#" @click.prevent="onDeactivate" v-if="form.record_status === 'active'">
                  <i class="bi bi-person-x me-2"></i>Deactivate User
                </a>
                <a class="dropdown-item text-success" href="#" @click.prevent="onActivate" v-else>
                  <i class="bi bi-person-check me-2"></i>Activate User
                </a>
              </li>
            </ul>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="row mt-2 g-4">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">User Ref</label>
              <div class="col-sm-4">
                <input type="text" class="form-control font-monospace" v-model="form.ref" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="eu-full-name" class="col-sm-4 col-form-label">Full Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="eu-full-name" placeholder="Full name" v-model="form.full_name" :disabled="!userLoaded" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="eu-email" class="col-sm-4 col-form-label">Email</label>
              <div class="col-sm-8">
                <input type="email" class="form-control" id="eu-email" placeholder="user@example.com" v-model="form.email" :disabled="!userLoaded" />
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
                    <input class="form-check-input" type="checkbox" :id="'eu-role-' + role.ref" :value="role.ref" v-model="form.role_refs" :disabled="!userLoaded" />
                    <label class="form-check-label" :for="'eu-role-' + role.ref">{{ role.name }}</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6 d-flex align-items-center">
                <span class="badge text-bg-success fs-6" v-if="form.record_status === 'active'">Active</span>
                <span class="badge text-bg-danger fs-6" v-else>Inactive</span>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">{{ savedMessage }}</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/edit_deactivate_user_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/company_management/user_management/edit_deactivate_user/edit_deactivate_user.js"></script>
