<?php /* modules/finance/financial_settings/financial_settings.php — Financial Settings editor */ ?>

<div id="fst-app" class="row g-4">

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
          <div class="card-title"><i class="bi bi-sliders me-2"></i>{{ group }}</div>
        </div>
        <div class="card-body">
          <div class="row">
            <div v-for="s in items" :key="s.setting_key" class="col-md-6 mb-4">
              <label class="form-label fw-semibold">
                {{ s.setting_label }}
                <span v-if="s.is_system" class="badge text-bg-secondary ms-1 small">system</span>
              </label>

              <!-- boolean toggle -->
              <div v-if="s.setting_type === 'boolean'" class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" :id="'fst-' + s.setting_key"
                  :checked="s.setting_value === 'true'"
                  @change="update(s, $event.target.checked ? 'true' : 'false')" />
                <label class="form-check-label" :for="'fst-' + s.setting_key">
                  {{ s.setting_value === 'true' ? 'Enabled' : 'Disabled' }}
                </label>
              </div>

              <!-- number input -->
              <input v-else-if="s.setting_type === 'number'" type="number" class="form-control"
                :value="s.setting_value"
                @blur="update(s, $event.target.value)"
                @keydown.enter="$event.target.blur()" />

              <!-- select -->
              <select v-else-if="s.setting_type === 'select'" class="form-select"
                :value="s.setting_value"
                @change="update(s, $event.target.value)">
                <option v-for="opt in parseOptions(s.options)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>

              <!-- date -->
              <input v-else-if="s.setting_type === 'date'" type="date" class="form-control"
                :value="s.setting_value"
                @blur="update(s, $event.target.value)" />

              <!-- text (default) -->
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

  </div>

</div>

<script src="/modules/finance/compliance/financial_settings/financial_settings.js"></script>
