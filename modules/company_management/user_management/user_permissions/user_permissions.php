<?php /* modules/company_management/user_management/user_permissions/user_permissions.php — Role permission grid */ ?>

<div id="up-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header d-flex align-items-center">
        <div class="card-title mb-0">{{ title }}</div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
          <i class="bi bi-question-circle me-1"></i>Help
        </button>
      </div>
      <div class="card-body">

        <!-- Role selection row -->
        <div class="row mb-4">
          <label for="up-role-select" class="col-sm-2 col-form-label fw-semibold">Select Role</label>
          <div class="col-sm-7">
            <select id="up-role-select" class="form-select" v-model="selectedRoleRef" @change="onRoleChange">
              <option value="">— Select a role —</option>
              <option v-for="role in roles" :key="role.ref" :value="role.ref">{{ role.name }}</option>
            </select>
          </div>
        </div>

        <!-- Permissions — shown once a role is picked -->
        <div v-if="selectedRoleRef">

          <div v-if="loadingModules" class="text-center py-4">
            <span class="spinner-border spinner-border-sm me-2"></span> Loading modules…
          </div>

          <template v-else>

            <!-- One card per section -->
            <div v-for="section in sections" :key="section" class="card mb-3 border">

              <div class="card-header d-flex align-items-center py-2"
                   :style="sectionHeaderStyle(section)">
                <i class="bi me-2" :class="sectionIcon(section)"></i>
                <span class="fw-semibold">{{ section }}</span>
              </div>

              <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr class="border-bottom">
                      <th class="text-muted small fw-normal fst-italic">
                        <i class="bi bi-check2-square me-1"></i>Select all
                      </th>
                      <th class="text-center" v-for="a in actions" :key="'sel-' + a.key">
                        <input type="checkbox" class="form-check-input"
                               :checked="isColumnAllChecked(section, a.key)"
                               @change="toggleColumn(section, a.key)" />
                      </th>
                      <th class="text-center">
                        <input type="checkbox" class="form-check-input"
                               :checked="isSectionAllChecked(section)"
                               @change="toggleSection(section)" />
                      </th>
                    </tr>
                    <tr>
                      <th style="min-width:220px">Module</th>
                      <th class="text-center" v-for="a in actions" :key="a.key" style="width:70px">{{ a.label }}</th>
                      <th class="text-center" style="width:60px">All</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="mod in sectionModules(section)" :key="mod.ref">
                      <td>
                        <span class="text-muted small" v-if="mod.subsection">{{ mod.subsection }} — </span>{{ mod.module }}
                      </td>
                      <td class="text-center" v-for="a in actions" :key="a.key">
                        <input type="checkbox" class="form-check-input"
                               :checked="isChecked(mod.ref, a.key)"
                               @change="toggle(mod.ref, a.key)" />
                      </td>
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input"
                               :checked="isRowAllChecked(mod.ref)"
                               @change="toggleRow(mod.ref)" />
                      </td>
                    </tr>
                  </tbody>
                </table>
                </div>
              </div>

            </div>

          </template>

        </div>

        <div v-else class="text-center text-muted py-5">
          <i class="bi bi-shield-lock fs-1 d-block mb-2 opacity-25"></i>
          Please select a role above to manage its module permissions.
        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Permissions saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto"
                @click="onSave" :disabled="!selectedRoleRef || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/company_management/user_management/user_permissions/user_permissions.js"></script>
