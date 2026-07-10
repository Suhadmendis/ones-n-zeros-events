<?php /* entries/password_change/password_change.php — Password change form */ ?>

<div id="password-change-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#passwordChangeSearchModal">Search User</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="row mt-2 g-4">

          <div class="col-md-6">
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">User Ref</label>
              <div class="col-sm-4">
                <input type="text" class="form-control font-monospace" v-model="user.ref" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Full Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" v-model="user.full_name" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Email</label>
              <div class="col-sm-8">
                <input type="email" class="form-control" v-model="user.email" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="pc-new-password" class="col-sm-4 col-form-label">New Password</label>
              <div class="col-sm-8">
                <input type="password" class="form-control" id="pc-new-password" placeholder="Enter new password" v-model="form.new_password" autocomplete="new-password" :disabled="!userLoaded" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="pc-confirm-password" class="col-sm-4 col-form-label">Confirm Password</label>
              <div class="col-sm-8">
                <input type="password" class="form-control" :class="{'is-invalid': passwordMismatch}" id="pc-confirm-password" placeholder="Confirm new password" v-model="form.confirm_password" autocomplete="new-password" :disabled="!userLoaded" />
                <div class="invalid-feedback">Passwords do not match.</div>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Password updated successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || !userLoaded || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/password_change_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/company_management/user_management/password_change/password_change.js"></script>
