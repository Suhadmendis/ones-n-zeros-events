<?php /* modules/settings/modules_mgmt/stg_enable_modules/stg_enable_modules.php */ ?>

<div id="enable-modules-app" class="row g-4">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header d-flex align-items-center">
        <div class="card-title mb-0">{{ title }}</div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
          <i class="bi bi-question-circle me-1"></i>Help
        </button>
      </div>
      <div class="card-body p-0">

        <div v-if="loading" class="text-center py-5">
          <span class="spinner-border text-primary" role="status"></span>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Section</th>
                <th class="text-center" style="width:90px">Icon</th>
                <th class="text-center" style="width:90px">Color</th>
                <th class="text-center" style="width:110px">Sort Order</th>
                <th class="text-center" style="width:140px">Web Enable</th>
                <th class="text-center" style="width:140px">App Enable</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="section in sections" :key="section.ref">
                <td class="ps-3">
                  <span class="fw-semibold">{{ section.name }}</span>
                  <small class="text-muted d-block">{{ section.ref }}</small>
                </td>
                <td class="text-center">
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                      data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false"
                      :title="section.web_icon">
                      <i class="bi" :class="section.web_icon || 'bi-circle'"></i>
                    </button>
                    <ul class="dropdown-menu p-2" style="width:150px">
                      <li class="d-flex flex-wrap gap-1">
                        <button v-for="icon in iconChoices(section)" :key="icon" type="button"
                          class="btn btn-sm"
                          :class="icon === section.web_icon ? 'btn-primary' : 'btn-light'"
                          :title="icon"
                          @click="onIconSelect(section, icon)">
                          <i class="bi" :class="icon"></i>
                        </button>
                      </li>
                    </ul>
                  </div>
                </td>
                <td class="text-center">
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                      data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false"
                      :title="section.web_color">
                      <span class="d-inline-block rounded-circle"
                        :style="{ backgroundColor: colorVar(section.web_color), width: '14px', height: '14px' }"></span>
                    </button>
                    <ul class="dropdown-menu p-2" style="width:190px">
                      <li class="d-flex flex-wrap gap-2">
                        <button v-for="color in colorChoices" :key="color" type="button"
                          class="btn btn-sm p-1 rounded-circle d-flex align-items-center justify-content-center"
                          :class="color === section.web_color ? 'border border-3 border-dark' : 'border'"
                          :title="color"
                          style="width:28px;height:28px"
                          @click="onColorSelect(section, color)">
                          <span class="d-inline-block rounded-circle" :style="{ backgroundColor: colorVar(color), width: '100%', height: '100%' }"></span>
                        </button>
                      </li>
                    </ul>
                  </div>
                </td>
                <td class="text-center text-muted">{{ section.sort_order }}</td>
                <td class="text-center">
                  <div class="form-check form-switch d-flex justify-content-center mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                      :id="'web-' + section.ref"
                      :checked="section.web_enable == 1"
                      @click.prevent="onToggleClick(section, 'web_enable')" />
                  </div>
                </td>
                <td class="text-center">
                  <div class="form-check form-switch d-flex justify-content-center mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                      :id="'app-' + section.ref"
                      :checked="section.app_enable == 1"
                      @click.prevent="onToggleClick(section, 'app_enable')" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
      <div class="card-footer text-muted small">
        Toggles take effect immediately after confirmation.
      </div>
    </div>
  </div>
</div>

<!-- Confirmation modal -->
<div class="modal fade" id="confirmToggleModal" tabindex="-1" aria-labelledby="confirmToggleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmToggleModalLabel">Confirm Change</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="confirm-toggle-body">
        Are you sure you want to change this setting?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirm-toggle-yes">Yes, Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/settings/modules_mgmt/stg_enable_modules/stg_enable_modules.js"></script>
