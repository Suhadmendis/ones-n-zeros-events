<?php /* modules/settings/financial_settings/stg_posting_rules/stg_posting_rules.php */ ?>

<div id="pr-app">

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h6 class="mb-0 fw-semibold">Posting Rules</h6>
      <small class="text-muted">Define which accounts are debited and credited for each module event.</small>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm module-help-btn" title="Help">
        <i class="bi bi-question-circle me-1"></i>Help
      </button>
      <button type="button" class="btn btn-success px-4" @click="saveAll" :disabled="saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
        <i v-else class="bi bi-check-all me-2"></i>Save All
      </button>
    </div>
  </div>

  <!-- Loading -->
  <div v-if="loading" class="text-center py-5">
    <span class="spinner-border text-primary"></span>
  </div>

  <!-- Module cards -->
  <template v-else>
    <div v-for="mod in modules" :key="mod.system_name" class="card card-outline card-secondary mb-3">

      <!-- Card header — clickable to expand -->
      <div class="card-header d-flex align-items-center justify-content-between"
           style="cursor:pointer" @click="toggleExpand(mod.system_name)">
        <div class="d-flex align-items-center gap-2">
          <i class="bi" :class="expanded[mod.system_name] ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
          <span class="fw-semibold">{{ mod.module }}</span>
          <span class="badge text-bg-secondary" style="font-size:.72rem">{{ mod.section }}</span>
          <span v-if="mod.subsection" class="badge text-bg-light border text-muted" style="font-size:.72rem">{{ mod.subsection }}</span>
        </div>
        <span v-if="ruleCount(mod.system_name)" class="badge text-bg-primary ms-2">
          {{ ruleCount(mod.system_name) }} lines
        </span>
      </div>

      <!-- Card body -->
      <div v-show="expanded[mod.system_name]" class="card-body p-0">

        <!-- Variant sections -->
        <div v-for="variant in getVariants(mod.system_name)" :key="variant ?? '__default__'">

          <!-- Variant label (only shown when there are multiple variants) -->
          <div v-if="getVariants(mod.system_name).length > 1"
               class="px-3 pt-3 pb-1">
            <span class="badge text-bg-info text-uppercase" style="font-size:.72rem;letter-spacing:.05em">
              {{ variant ?? 'Default' }}
            </span>
          </div>

          <!-- Rules table -->
          <div class="px-3 pt-2" style="padding-bottom:160px;overflow:visible">
            <table class="table table-sm table-bordered align-middle mb-1" style="font-size:.85rem">
              <thead class="table-light">
                <tr>
                  <th style="width:36px">#</th>
                  <th style="width:100px">Type</th>
                  <th style="width:120px">Account Code</th>
                  <th style="width:220px">Account Name</th>
                  <th>Description</th>
                  <th style="width:36px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, idx) in getLines(mod.system_name, variant)" :key="idx">
                  <td class="text-center text-muted small">{{ idx + 1 }}</td>

                  <!-- Entry type -->
                  <td>
                    <select class="form-select form-select-sm"
                            :class="line.entry_type === 'debit' ? 'text-danger' : 'text-success'"
                            v-model="line.entry_type">
                      <option value="debit">Debit</option>
                      <option value="credit">Credit</option>
                    </select>
                  </td>

                  <!-- Account code with autocomplete -->
                  <td style="position:relative">
                    <input type="text"
                           class="form-control form-control-sm font-monospace"
                           v-model="line.account_code"
                           @input="onCodeInput(mod.system_name, variant, idx, $event.target.value)"
                           @blur="closeSuggestions"
                           @keydown.esc="closeSuggestions"
                           @keydown.down.prevent="moveSuggestion(1)"
                           @keydown.up.prevent="moveSuggestion(-1)"
                           @keydown.enter.prevent="pickActive(mod.system_name, variant, idx)"
                           autocomplete="off"
                           placeholder="e.g. 1100" />
                    <ul v-if="activeCell && activeCell.module === mod.system_name && activeCell.variant === (variant ?? null) && activeCell.idx === idx && suggestions.length"
                        class="list-group position-absolute shadow"
                        style="z-index:9999;top:100%;left:0;min-width:280px;max-height:180px;overflow-y:auto">
                      <li v-for="(s, si) in suggestions" :key="s.account_code"
                          class="list-group-item list-group-item-action py-1 px-2 small"
                          :class="{ active: si === activeSuggestionIdx }"
                          @mousedown.prevent="pickSuggestion(mod.system_name, variant, idx, s)">
                        <span class="font-monospace fw-semibold me-2">{{ s.account_code }}</span>
                        <span>{{ s.account_name }}</span>
                      </li>
                    </ul>
                  </td>

                  <!-- Account name (auto-filled, read-only) -->
                  <td>
                    <input type="text" class="form-control form-control-sm" v-model="line.account_name" readonly />
                  </td>

                  <!-- Description -->
                  <td>
                    <input type="text" class="form-control form-control-sm" v-model="line.description" placeholder="Optional" />
                  </td>

                  <!-- Remove -->
                  <td class="text-center">
                    <button type="button" class="btn btn-link btn-sm text-danger p-0"
                            @click="removeLine(mod.system_name, variant, idx)"
                            title="Remove line">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </td>
                </tr>

                <!-- Empty state -->
                <tr v-if="!getLines(mod.system_name, variant).length">
                  <td colspan="6" class="text-center text-muted py-2 small">No lines. Add at least one debit and one credit.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Add line -->
          <div class="px-3 pb-2">
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    @click="addLine(mod.system_name, variant)">
              <i class="bi bi-plus me-1"></i>Add Line
            </button>
          </div>

        </div><!-- /variant -->

        <!-- Per-module save footer -->
        <div class="card-footer d-flex align-items-center gap-3">
          <span v-if="errors[mod.system_name]" class="text-danger small">
            <i class="bi bi-exclamation-circle me-1"></i>{{ errors[mod.system_name] }}
          </span>
          <span v-if="savedFlag[mod.system_name]" class="text-success small">
            <i class="bi bi-check-lg me-1"></i>Saved
          </span>
          <button type="button" class="btn btn-primary btn-sm ms-auto px-4"
                  @click="saveModule(mod.system_name)"
                  :disabled="savingModule[mod.system_name]">
            <span v-if="savingModule[mod.system_name]" class="spinner-border spinner-border-sm me-1"></span>
            Save
          </button>
        </div>

      </div><!-- /card-body -->
    </div><!-- /module card -->

    <div v-if="!modules.length" class="text-center text-muted py-5">
      No modules configured for journal entries.
    </div>
  </template>

</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page ?? '') ?>';</script>
<script src="/modules/settings/financial_settings/stg_posting_rules/stg_posting_rules.js"></script>
