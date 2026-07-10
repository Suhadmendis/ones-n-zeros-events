<?php /* modules/settings/modules_mgmt/module_generator/module_generator.php */ ?>

<div id="mgen-app" class="row g-4" v-cloak>
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header d-flex align-items-center">
        <div class="card-title mb-0">{{ title }}</div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
          <i class="bi bi-question-circle me-1"></i>Help
        </button>
      </div>
      <div class="card-body">

        <p class="text-muted small">
          Registers a new module in the ERP catalog and writes a working file scaffold —
          a flat data-entry form, a header + line-items document, or a date-range report —
          fully wired to the fields you define below.
        </p>

        <div class="row mb-3">
          <label for="mgen-section" class="col-sm-3 col-form-label">Section</label>
          <div class="col-sm-9 col-lg-6">
            <select id="mgen-section" class="form-select" v-model="selectedSectionRef"
                    @change="onSectionChange" :disabled="loadingLookups || generating">
              <option value="" disabled>{{ loadingLookups ? 'Loading…' : 'Select a section…' }}</option>
              <option v-for="s in sections" :key="s.ref" :value="s.ref">{{ s.name }}</option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <label for="mgen-subsection" class="col-sm-3 col-form-label">Subsection</label>
          <div class="col-sm-9 col-lg-6">
            <select id="mgen-subsection" class="form-select" v-model="selectedSubsectionRef"
                    @change="onSubsectionChange" :disabled="!selectedSectionRef || generating">
              <option value="" disabled>{{ selectedSectionRef ? 'Select a subsection…' : 'Select a section first' }}</option>
              <option v-for="s in filteredSubsections" :key="s.ref" :value="s.ref">{{ s.name }}</option>
            </select>
          </div>
        </div>

        <div class="row mb-2">
          <label for="mgen-name" class="col-sm-3 col-form-label">Module Name</label>
          <div class="col-sm-9 col-lg-6">
            <input type="text" class="form-control" id="mgen-name"
                   placeholder="e.g. Vehicle Insurance Claims"
                   v-model.trim="moduleName" @input="onModuleNameInput" :disabled="generating" />
          </div>
        </div>

        <div class="row mb-3" v-if="moduleName">
          <div class="col-sm-9 col-lg-6 offset-sm-3">
            <small class="text-muted">
              Folder: <code>{{ slugPreview }}</code>
              <span v-if="slugCheck.status === 'checking'" class="text-muted ms-2">
                <span class="spinner-border spinner-border-sm"></span> checking…
              </span>
              <span v-else-if="slugCheck.status === 'available'" class="text-success ms-2">
                <i class="bi bi-check-circle-fill"></i> available
              </span>
              <span v-else-if="slugCheck.status === 'taken'" class="text-danger ms-2">
                <i class="bi bi-x-circle-fill"></i> already exists
              </span>
              <span v-else-if="slugCheck.status === 'invalid'" class="text-danger ms-2">
                <i class="bi bi-x-circle-fill"></i> needs at least 3 letters/numbers
              </span>
            </small>
          </div>
        </div>

        <div class="row mb-4" v-if="moduleName">
          <label class="col-sm-3 col-form-label">Module Type</label>
          <div class="col-sm-9 col-lg-8">
            <div class="btn-group" role="group">
              <input type="radio" class="btn-check" id="mgen-type-entry" value="entry" v-model="moduleType" :disabled="generating" />
              <label class="btn btn-outline-primary" for="mgen-type-entry">Normal Entry</label>

              <input type="radio" class="btn-check" id="mgen-type-header-detail" value="header_detail" v-model="moduleType" :disabled="generating" />
              <label class="btn btn-outline-primary" for="mgen-type-header-detail">Header + Line Items</label>

              <input type="radio" class="btn-check" id="mgen-type-report" value="report" v-model="moduleType" :disabled="generating" />
              <label class="btn btn-outline-primary" for="mgen-type-report">Report</label>
            </div>
            <small class="d-block mt-1 text-muted">
              <span v-if="moduleType === 'entry'">A single data-entry form with a Reference No. and the fields you define.</span>
              <span v-else-if="moduleType === 'header_detail'">A document header plus an optional repeatable line-items table (e.g. a quotation or invoice). Leave line fields empty if you don't need a lines table.</span>
              <span v-else>A date-range report screen — Run, then Export Excel / Print. The aggregation query is scaffolded as a TODO; you write the real query afterward.</span>
            </small>
          </div>
        </div>

        <template v-if="moduleName && moduleType !== 'report'">

          <div class="row mb-3">
            <label for="mgen-table" class="col-sm-3 col-form-label">Table Name</label>
            <div class="col-sm-9 col-lg-6">
              <input type="text" class="form-control font-monospace" id="mgen-table"
                     v-model.trim="tableName" @input="onTableNameInput" :disabled="generating" />
              <small class="d-block mt-1">
                <span v-if="tableCheck.status === 'checking'" class="text-muted">
                  <span class="spinner-border spinner-border-sm"></span> checking…
                </span>
                <span v-else-if="tableCheck.status === 'available'" class="text-success">
                  <i class="bi bi-check-circle-fill"></i> available
                </span>
                <span v-else-if="tableCheck.status === 'taken'" class="text-danger">
                  <i class="bi bi-x-circle-fill"></i> already exists
                </span>
                <span v-else-if="tableCheck.status === 'invalid'" class="text-danger">
                  <i class="bi bi-x-circle-fill"></i> must start with <code>m_</code>, lowercase letters/numbers/underscores only
                </span>
                <span v-else class="text-muted">Auto-filled from the module name — change it if you'd rather use a different table.</span>
              </small>
            </div>
          </div>

          <div class="row mb-4">
            <label for="mgen-prefix" class="col-sm-3 col-form-label">Reference Prefix</label>
            <div class="col-sm-9 col-lg-6">
              <input type="text" class="form-control font-monospace" id="mgen-prefix" style="max-width:12rem"
                     v-model.trim="refPrefix" @input="onRefPrefixInput" :disabled="generating" />
              <small class="d-block mt-1">
                <span v-if="refPrefix && !refPrefixValid" class="text-danger">
                  <i class="bi bi-x-circle-fill"></i> 2–6 letters/numbers, e.g. <code>VIC</code>
                </span>
                <span v-else class="text-muted">Used to build reference numbers, e.g. <code>{{ refPrefix || 'ABC' }}-0000001</code>.</span>
              </small>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex align-items-center mb-2">
              <h6 class="mb-0">{{ moduleType === 'header_detail' ? 'Header Fields' : 'Fields' }}</h6>
              <button type="button" class="btn btn-sm btn-outline-primary ms-auto" @click="addField" :disabled="generating">
                <i class="bi bi-plus-lg me-1"></i>Add Field
              </button>
            </div>

            <p v-if="!fields.length" class="text-muted small">
              No business fields yet — the module will only have Reference No. Add fields below, or generate as-is and edit the files afterward.
            </p>

            <div v-for="(f, idx) in fields" :key="f._id" class="row g-2 align-items-start mb-2">
              <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" placeholder="Label"
                       v-model.trim="f.label" @input="onFieldLabelInput(f)" :disabled="generating" />
              </div>
              <div class="col-md-3">
                <input type="text" class="form-control form-control-sm font-monospace" placeholder="column_name"
                       v-model.trim="f.column" @input="f.columnTouched = true" :disabled="generating" />
              </div>
              <div class="col-md-2">
                <select class="form-select form-select-sm" v-model="f.type" :disabled="generating">
                  <option value="text">Text</option>
                  <option value="textarea">Textarea</option>
                  <option value="number">Number</option>
                  <option value="decimal">Decimal</option>
                  <option value="date">Date</option>
                  <option value="dropdown">Dropdown</option>
                  <option value="checkbox">Checkbox</option>
                </select>
              </div>
              <div class="col-md-3">
                <input v-if="f.type === 'dropdown'" type="text" class="form-control form-control-sm"
                       placeholder="Option A, Option B, ..." v-model.trim="f.options" :disabled="generating" />
              </div>
              <div class="col-md-auto d-flex align-items-center gap-2" style="height:31px">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" v-model="f.required" :id="'mgen-freq-' + f._id" :disabled="generating" />
                  <label class="form-check-label small" :for="'mgen-freq-' + f._id">Required</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeField(idx)" :disabled="generating">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
              <div class="col-12" v-if="fieldErrors[f._id]">
                <small class="text-danger">{{ fieldErrors[f._id] }}</small>
              </div>
            </div>
          </div>

          <div v-if="moduleType === 'header_detail'" class="mb-4 p-3 border rounded-3 bg-body-tertiary">
            <div class="d-flex align-items-center mb-2">
              <h6 class="mb-0">Line Item Fields</h6>
              <button type="button" class="btn btn-sm btn-outline-primary ms-auto" @click="addLineField" :disabled="generating">
                <i class="bi bi-plus-lg me-1"></i>Add Line Field
              </button>
            </div>

            <p class="text-muted small">
              Leave empty if this module doesn't need a line-items table — it will generate
              exactly like a Normal Entry module. Adding fields here creates a child table
              with an "Add Line" table on the form.
            </p>

            <div v-for="(f, idx) in lineFields" :key="f._id" class="row g-2 align-items-start mb-2">
              <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" placeholder="Label"
                       v-model.trim="f.label" @input="onLineFieldLabelInput(f)" :disabled="generating" />
              </div>
              <div class="col-md-3">
                <input type="text" class="form-control form-control-sm font-monospace" placeholder="column_name"
                       v-model.trim="f.column" @input="f.columnTouched = true" :disabled="generating" />
              </div>
              <div class="col-md-2">
                <select class="form-select form-select-sm" v-model="f.type" :disabled="generating">
                  <option value="text">Text</option>
                  <option value="textarea">Textarea</option>
                  <option value="number">Number</option>
                  <option value="decimal">Decimal</option>
                  <option value="date">Date</option>
                  <option value="dropdown">Dropdown</option>
                  <option value="checkbox">Checkbox</option>
                </select>
              </div>
              <div class="col-md-3">
                <input v-if="f.type === 'dropdown'" type="text" class="form-control form-control-sm"
                       placeholder="Option A, Option B, ..." v-model.trim="f.options" :disabled="generating" />
              </div>
              <div class="col-md-auto d-flex align-items-center gap-2" style="height:31px">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" v-model="f.required" :id="'mgen-lfreq-' + f._id" :disabled="generating" />
                  <label class="form-check-label small" :for="'mgen-lfreq-' + f._id">Required</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLineField(idx)" :disabled="generating">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
              <div class="col-12" v-if="lineFieldErrors[f._id]">
                <small class="text-danger">{{ lineFieldErrors[f._id] }}</small>
              </div>
            </div>
          </div>

          <div class="mb-4 p-3 border rounded-3">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" role="switch" id="mgen-gl-toggle"
                     v-model="createsJournalEntry" :disabled="generating" />
              <label class="form-check-label fw-semibold" for="mgen-gl-toggle">Post to General Ledger</label>
            </div>

            <template v-if="createsJournalEntry">
              <p class="text-muted small">
                Every save auto-posts a balanced journal entry. Accounts are read from
                <code>m_posting_rules</code> at save time — changeable later from
                Settings → Posting Rules without touching code.
              </p>

              <div class="row mb-3">
                <label for="mgen-gl-amount" class="col-sm-3 col-form-label">Amount Field</label>
                <div class="col-sm-9 col-lg-6">
                  <select id="mgen-gl-amount" class="form-select" v-model="glAmountField" :disabled="generating">
                    <option value="" disabled>{{ numericFields.length ? 'Select a field…' : 'Add a Number/Decimal field first' }}</option>
                    <option v-for="f in numericFields" :key="f._id" :value="f.column">{{ f.label || f.column }}</option>
                  </select>
                </div>
              </div>

              <div class="d-flex align-items-center mb-2">
                <label class="mb-0 small fw-semibold">Posting Lines</label>
                <button type="button" class="btn btn-sm btn-outline-primary ms-auto" @click="addGlLine" :disabled="generating">
                  <i class="bi bi-plus-lg me-1"></i>Add Line
                </button>
              </div>

              <div v-for="(l, idx) in glLines" :key="l._id" class="row g-2 align-items-start mb-2">
                <div class="col-md-2">
                  <select class="form-select form-select-sm" v-model="l.entry_type" :disabled="generating">
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <input type="text" class="form-control form-control-sm font-monospace" placeholder="Account Code"
                         v-model.trim="l.account_code" :disabled="generating" />
                </div>
                <div class="col-md-3">
                  <input type="text" class="form-control form-control-sm" placeholder="Account Name"
                         v-model.trim="l.account_name" :disabled="generating" />
                </div>
                <div class="col-md-3">
                  <input type="text" class="form-control form-control-sm" placeholder="Description (optional)"
                         v-model.trim="l.description" :disabled="generating" />
                </div>
                <div class="col-md-auto">
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeGlLine(idx)" :disabled="generating">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
              <small v-if="glLinesError" class="text-danger d-block mt-1">{{ glLinesError }}</small>
            </template>
          </div>

        </template>

        <div v-if="generated" class="alert alert-success">
          <div class="fw-semibold mb-1"><i class="bi bi-check-circle-fill me-1"></i>Module generated</div>
          <div><strong>{{ generated.ref }}</strong> — {{ generated.name }}</div>
          <div class="small text-muted mb-2">
            {{ generated.section }} / {{ generated.subsection }} / {{ generated.folder }} — type <code>{{ generated.module_type }}</code>
            <template v-if="generated.table"> — table <code>{{ generated.table }}</code></template>
            <template v-if="generated.line_table"> + line table <code>{{ generated.line_table }}</code></template>
            <template v-if="generated.ref_prefix"> — ref prefix <code>{{ generated.ref_prefix }}</code></template>
          </div>
          <a :href="generated.url" target="_blank" class="btn btn-sm btn-outline-success">
            Open the new module <i class="bi bi-box-arrow-up-right ms-1"></i>
          </a>
        </div>

        <div v-if="generated && generated.warnings && generated.warnings.length" class="alert alert-warning">
          <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Still worth a look</div>
          <ul class="mb-0 small">
            <li v-for="w in generated.warnings" :key="w">{{ w }}</li>
          </ul>
        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto"
                @click="onGenerate" :disabled="!canGenerate || generating">
          <span v-if="generating" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-magic me-2"></i>Generate Module
        </button>
      </div>
    </div>
  </div>
</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/settings/modules_mgmt/module_generator/module_generator.js"></script>
