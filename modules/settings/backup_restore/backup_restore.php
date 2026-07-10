<?php /* modules/settings/backup_restore/backup_restore.php — Backup & Restore Settings editor */ ?>

<div id="bkp-app" class="row g-4">

  <div class="col-12">
    <div class="d-flex justify-content-end mb-2">
      <button type="button" class="btn btn-outline-secondary btn-sm module-help-btn" title="Help">
        <i class="bi bi-question-circle me-1"></i>Help
      </button>
    </div>

    <div v-if="loading" class="text-center py-5">
      <span class="spinner-border text-primary"></span>
    </div>

    <template v-else>
      <div v-for="(items, group) in grouped" :key="group" class="card card-outline card-secondary mb-4">
        <div class="card-header">
          <div class="card-title"><i class="bi bi-cloud-arrow-up me-2"></i>{{ group }}</div>
        </div>
        <div class="card-body">
          <div class="row">
            <div v-for="s in items" :key="s.setting_key" class="col-md-6 mb-4">
              <label class="form-label fw-semibold">
                {{ s.setting_label }}
                <span v-if="s.is_system" class="badge text-bg-secondary ms-1 small">system</span>
              </label>

              <div v-if="s.setting_type === 'boolean'" class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" :id="'bkp-' + s.setting_key"
                  :checked="s.setting_value === 'true'"
                  @change="update(s, $event.target.checked ? 'true' : 'false')" />
                <label class="form-check-label" :for="'bkp-' + s.setting_key">
                  {{ s.setting_value === 'true' ? 'Enabled' : 'Disabled' }}
                </label>
              </div>

              <input v-else-if="s.setting_type === 'number'" type="number" class="form-control"
                :value="s.setting_value"
                @blur="update(s, $event.target.value)"
                @keydown.enter="$event.target.blur()" />

              <select v-else-if="s.setting_type === 'select'" class="form-select"
                :value="s.setting_value"
                @change="update(s, $event.target.value)">
                <option v-for="opt in parseOptions(s.options)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>

              <input v-else-if="s.setting_type === 'date'" type="date" class="form-control"
                :value="s.setting_value"
                @blur="update(s, $event.target.value)" />

              <input v-else type="text" class="form-control"
                :value="s.setting_value"
                @blur="update(s, $event.target.value)"
                @keydown.enter="$event.target.blur()" />

              <div v-if="s.description" class="form-text text-muted small mt-1">{{ s.description }}</div>
              <div v-if="saved[s.setting_key]" class="text-success small mt-1"><i class="bi bi-check-lg me-1"></i>Saved</div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Manual backup trigger -->
    <div class="card card-outline card-info mb-4">
      <div class="card-header"><div class="card-title"><i class="bi bi-archive me-2"></i>Manual Actions</div></div>
      <div class="card-body d-flex gap-3 flex-wrap">
        <button class="btn btn-primary" @click="triggerBackup" :disabled="backupRunning">
          <span v-if="backupRunning" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="bi bi-cloud-upload me-2"></i>Run Backup Now
        </button>
        <span v-if="backupMsg" class="align-self-center small" :class="backupMsg.ok ? 'text-success' : 'text-danger'">
          <i :class="backupMsg.ok ? 'bi bi-check-lg' : 'bi bi-x-lg'" class="me-1"></i>{{ backupMsg.text }}
        </span>
      </div>
    </div>

  </div>

</div>

<script src="/modules/settings/backup_restore/backup_restore.js"></script>
