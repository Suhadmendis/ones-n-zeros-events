<?php /* modules/settings/financial_settings/stg_user_preferences/stg_user_preferences.php */ ?>

<div id="uprefs-app">

  <div class="row justify-content-center">
    <div class="col-lg-7">

      <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center">
          <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-sliders me-2 text-primary"></i>Entry Form Preferences
          </h6>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="card-body">
          <div v-if="loading" class="text-center py-4">
            <span class="spinner-border text-primary"></span>
          </div>
          <template v-else>

            <!-- Check GL default -->
            <div class="uprefs-row" :class="{ 'uprefs-row--on': prefs.check_gl_default }">
              <div class="uprefs-row__info">
                <div class="uprefs-row__label">General Ledger Preview — Check GL</div>
                <div class="uprefs-row__desc">
                  When enabled, the journal entry preview panel opens automatically
                  each time you enter a transaction. You can still toggle it on or off
                  within any individual entry form.
                </div>
              </div>
              <div class="form-check form-switch uprefs-row__toggle">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="pref-checkGL" v-model="prefs.check_gl_default">
              </div>
            </div>

          </template>
        </div>

        <div class="card-footer d-flex align-items-center gap-3">
          <span v-if="error" class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>{{ error }}</span>
          <span v-if="saved" class="text-success small"><i class="bi bi-check-lg me-1"></i>Preferences saved.</span>
          <button type="button" class="btn btn-primary ms-auto px-4" @click="save" :disabled="saving || loading">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-floppy me-2"></i>Save
          </button>
        </div>
      </div>

    </div>
  </div>

</div>

<script src="/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences.js"></script>

<style>
.uprefs-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 18px 0;
  border-bottom: 1px solid #f1f3f5;
}
.uprefs-row:last-child { border-bottom: none; }
.uprefs-row--on .uprefs-row__label { color: #0d6efd; }

.uprefs-row__info   { flex: 1; }
.uprefs-row__label  { font-weight: 600; font-size: .88rem; color: #212529; margin-bottom: 4px; }
.uprefs-row__desc   { font-size: .78rem; color: #6c757d; line-height: 1.55; }

.uprefs-row__toggle .form-check-input {
  width: 2.6em;
  height: 1.35em;
  cursor: pointer;
  margin: 0;
}
</style>
