<?php /* modules/company_management/user_management/role_management/role_management.php — Role CRUD */ ?>

<div id="role-mgmt-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header d-flex align-items-center">
        <div class="card-title mb-0">{{ title }}</div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
          <i class="bi bi-question-circle me-1"></i>Help
        </button>
      </div>
      <div class="card-body">

        <div class="row g-4">

          <!-- Form -->
          <div class="col-md-5">
            <h6 class="fw-semibold mb-3">{{ editingRef ? 'Edit Role' : 'New Role' }}</h6>
            <div class="mb-3">
              <label for="rm-name" class="form-label">Role Name</label>
              <input type="text" class="form-control" id="rm-name" placeholder="e.g. Accountant" v-model="form.name" />
            </div>
            <div class="mb-3">
              <label for="rm-description" class="form-label">Description</label>
              <textarea class="form-control" id="rm-description" rows="3" v-model="form.description"></textarea>
            </div>
            <div class="mb-3" v-if="editingRef">
              <label for="rm-status" class="form-label">Status</label>
              <select class="form-select" id="rm-status" v-model="form.record_status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-success" @click="onSave" :disabled="!isDirty || saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ editingRef ? 'Update Role' : 'Create Role' }}
              </button>
              <button type="button" class="btn btn-secondary" @click="onNew" v-if="editingRef">Cancel</button>
            </div>
            <div class="mt-2">
              <span class="text-danger small" v-if="error">{{ error }}</span>
              <span class="text-success small" v-if="saved">Role saved successfully.</span>
            </div>
          </div>

          <!-- List -->
          <div class="col-md-7">
            <h6 class="fw-semibold mb-3">Roles</h6>
            <div v-if="loading" class="text-center py-4">
              <span class="spinner-border spinner-border-sm me-2"></span> Loading…
            </div>
            <table v-else class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr><th>Name</th><th>Description</th><th>Status</th><th></th></tr>
              </thead>
              <tbody>
                <tr v-for="role in roles" :key="role.ref">
                  <td class="fw-semibold">{{ role.name }}</td>
                  <td class="text-muted small">{{ role.description }}</td>
                  <td>
                    <span class="badge" :class="role.record_status === 'active' ? 'text-bg-success' : 'text-bg-danger'">
                      {{ role.record_status === 'active' ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="onEditRole(role)"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="onToggleStatus(role)">
                      <i class="bi" :class="role.record_status === 'active' ? 'bi-slash-circle' : 'bi-check-circle'"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

      </div>
    </div>
  </div>

</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/company_management/user_management/role_management/role_management.js"></script>
