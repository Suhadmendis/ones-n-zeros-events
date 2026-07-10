<?php /* modules/settings/modules_mgmt/module_documentation/module_documentation.php */ ?>

<div id="mdoc-app" class="row g-4" v-cloak>
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="row mb-3">
          <label for="mdoc-section" class="col-sm-3 col-form-label">Section</label>
          <div class="col-sm-9 col-lg-6">
            <select id="mdoc-section" class="form-select" v-model="selectedSectionRef" @change="onSectionChange" :disabled="loadingLookups">
              <option value="" disabled>{{ loadingLookups ? 'Loading…' : 'Select a section…' }}</option>
              <option v-for="s in sections" :key="s.ref" :value="s.ref">{{ s.name }}</option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <label for="mdoc-subsection" class="col-sm-3 col-form-label">Subsection</label>
          <div class="col-sm-9 col-lg-6">
            <select id="mdoc-subsection" class="form-select" v-model="selectedSubsectionRef" @change="onSubsectionChange" :disabled="!selectedSectionRef">
              <option value="" disabled>{{ selectedSectionRef ? 'Select a subsection…' : 'Select a section first' }}</option>
              <option v-for="s in filteredSubsections" :key="s.ref" :value="s.ref">{{ s.name }}</option>
            </select>
          </div>
        </div>

        <div class="row mb-4">
          <label for="mdoc-module" class="col-sm-3 col-form-label">Module</label>
          <div class="col-sm-9 col-lg-6">
            <select id="mdoc-module" class="form-select" v-model="selectedModuleRef" @change="onModuleChange" :disabled="!selectedSubsectionRef">
              <option value="" disabled>{{ selectedSubsectionRef ? 'Select a module…' : 'Select a subsection first' }}</option>
              <option v-for="m in filteredModules" :key="m.ref" :value="m.ref">{{ m.name }}</option>
            </select>
          </div>
        </div>

        <div v-if="selectedModuleRef">
          <div class="row mb-3">
            <label for="mdoc-title" class="col-sm-3 col-form-label">Title</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="mdoc-title" v-model="form.title" />
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-subtitle" class="col-sm-3 col-form-label">Subtitle</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="mdoc-subtitle" v-model="form.subtitle" />
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-purpose" class="col-sm-3 col-form-label">Purpose</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-purpose" rows="3" v-model="form.purpose"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-business-problem" class="col-sm-3 col-form-label">Business Problem</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-business-problem" rows="3" v-model="form.business_problem"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-business-impact" class="col-sm-3 col-form-label">Business Impact</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-business-impact" rows="3" v-model="form.business_impact"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-when-to-use" class="col-sm-3 col-form-label">When to Use</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-when-to-use" rows="3" v-model="form.when_to_use"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-workflow" class="col-sm-3 col-form-label">Workflow</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-workflow" rows="4" v-model="form.workflow"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-prerequisites" class="col-sm-3 col-form-label">Prerequisites</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-prerequisites" rows="3" v-model="form.prerequisites"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label for="mdoc-best-practices" class="col-sm-3 col-form-label">Best Practices</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-best-practices" rows="3" v-model="form.best_practices"></textarea>
            </div>
          </div>
          <div class="row">
            <label for="mdoc-notes" class="col-sm-3 col-form-label">Notes</label>
            <div class="col-sm-9">
              <textarea class="form-control" id="mdoc-notes" rows="3" v-model="form.notes"></textarea>
            </div>
          </div>
        </div>

        <div v-else class="text-muted py-4 text-center">
          Select a module above to view or edit its help documentation.
        </div>

      </div>
      <div class="card-footer d-flex align-items-center" v-if="selectedModuleRef">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Documentation saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="saving || loadingRecord">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/settings/modules_mgmt/module_documentation/module_documentation.js"></script>
