<?php /* modules/finance/journal_entries/journal_entries.php — Journal Entries form, included by home.php */ ?>

<div id="je-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#jeSearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <!-- Header fields -->
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <label class="form-label">Reference No.</label>
            <div class="input-group">
              <input type="text" class="form-control font-monospace" v-model="form.ref" disabled />
              <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" v-model="form.journal_date" @change="syncPeriod" :disabled="viewMode" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Period</label>
            <input type="month" class="form-control" v-model="form.period" :disabled="viewMode" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Reference Doc</label>
            <input type="text" class="form-control font-monospace" placeholder="Optional" v-model="form.reference_doc" :disabled="viewMode" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select" v-model="form.status" :disabled="viewMode">
              <option value="draft">Draft</option>
              <option value="posted">Posted</option>
            </select>
          </div>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label">Description</label>
            <input type="text" class="form-control" placeholder="Journal entry narration" v-model="form.description" :disabled="viewMode" />
          </div>
        </div>

        <!-- Line items -->
        <div class="table-responsive mb-3" style="overflow:visible;min-height:300px">
          <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:3%">#</th>
                <th style="width:9%">Code</th>
                <th style="width:22%">Account Name</th>
                <th style="width:4%"></th>
                <th>Description</th>
                <th style="width:12%">Debit (LKR)</th>
                <th style="width:12%">Credit (LKR)</th>
                <th style="width:4%"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, i) in form.lines" :key="i">
                <td class="text-center text-muted small">{{ i + 1 }}</td>
                <td style="position:relative">
                  <input type="text" class="form-control form-control-sm font-monospace je-code"
                         :class="{ 'is-invalid': lineErrors(line).code }"
                         v-model="line.account_code"
                         @input="onCodeInput(i)"
                         @blur="closeSuggestions"
                         @keydown.esc="closeSuggestions"
                         @keydown.down.prevent="onCodeKeyDown('down')"
                         @keydown.up.prevent="onCodeKeyDown('up')"
                         @keydown.enter.prevent="onCodeKeyEnter(i)"
                         autocomplete="off"
                         placeholder="e.g. 1000"
                         :disabled="viewMode" />
                  <ul v-if="activeInputLine === i && suggestions.length"
                      class="list-group position-absolute w-100 shadow"
                      style="z-index:9999;top:100%;left:0;max-height:180px;overflow-y:auto">
                    <li v-for="(s, j) in suggestions" :key="s.account_code"
                        class="list-group-item list-group-item-action py-1 px-2 small"
                        :class="{ active: j === activeSuggestionIndex }"
                        @mousedown.prevent="pickSuggestion(i, s)">
                      <span class="font-monospace fw-semibold me-2">{{ s.account_code }}</span>
                      <span :class="{ 'text-muted': j !== activeSuggestionIndex }">{{ s.account_name }}</span>
                    </li>
                  </ul>
                </td>
                <td><input type="text" class="form-control form-control-sm" v-model="line.account_name" readonly /></td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="openPicker(i)" title="Pick account" :disabled="viewMode">
                    <i class="bi bi-search"></i>
                  </button>
                </td>
                <td><input type="text" class="form-control form-control-sm" placeholder="Line narration" v-model="line.description" :disabled="viewMode" /></td>
                <td><input type="number" class="form-control form-control-sm text-end je-debit" :class="{ 'is-invalid': lineErrors(line).amount }" min="0" step="0.01" placeholder="0.00" v-model="line.debit_amount" @input="onDebitInput(i)" @keydown.enter.prevent="onDebitEnter(i)" :disabled="viewMode" /></td>
                <td><input type="number" class="form-control form-control-sm text-end je-credit" :class="{ 'is-invalid': lineErrors(line).amount }" min="0" step="0.01" placeholder="0.00" v-model="line.credit_amount" @input="onCreditInput(i)" @keydown.enter.prevent="onCreditEnter(i)" :disabled="viewMode" /></td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(i)" :disabled="form.lines.length <= 2 || viewMode">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="!viewMode" @click="addLine" style="cursor:pointer">
                <td colspan="8" class="text-center py-2 text-secondary"
                    style="border-top:2px dashed #dee2e6;background:#f8f9fa;"
                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                    title="&lt;b&gt;Ways to add a new line:&lt;/b&gt;&lt;br&gt;&amp;bull; Click this stripe&lt;br&gt;&amp;bull; Press Enter from the Code field&lt;br&gt;&amp;bull; Press Enter from the Debit field&lt;br&gt;&amp;bull; Press Enter from the Credit field">
                  <i class="bi bi-plus-lg me-1"></i>Add New Line
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="fw-bold">
                <td colspan="5" class="text-end">Totals</td>
                <td class="text-end font-monospace" :class="isBalanced ? 'text-success' : 'text-danger'">{{ fmtNum(totalDebit) }}</td>
                <td class="text-end font-monospace" :class="isBalanced ? 'text-success' : 'text-danger'">{{ fmtNum(totalCredit) }}</td>
                <td></td>
              </tr>
              <tr v-if="(totalDebit + totalCredit) > 0 && !isBalanced" class="fw-semibold text-danger small">
                <td colspan="5" class="text-end border-top-0 pt-0">Difference</td>
                <td class="text-end font-monospace border-top-0 pt-0">{{ totalDebit < totalCredit ? fmtNum(totalCredit - totalDebit) : '' }}</td>
                <td class="text-end font-monospace border-top-0 pt-0">{{ totalCredit < totalDebit ? fmtNum(totalDebit - totalCredit) : '' }}</td>
                <td class="border-top-0 pt-0"></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <ul class="list-unstyled mb-3 small" v-if="validationHints.length">
          <li v-for="hint in validationHints" :key="hint.text"
              class="d-flex align-items-start gap-2 mb-1"
              :class="hint.ok ? 'text-success' : 'text-warning-emphasis'">
            <i class="bi flex-shrink-0 mt-1" :class="hint.ok ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-warning'"></i>
            <span>{{ hint.text }}</span>
          </li>
        </ul>

      </div>
      <div class="card-footer d-flex align-items-center gap-3 flex-wrap">
        <div class="text-muted small lh-sm" v-if="viewMode && (form.drafted_by || form.drafted_at || form.posted_by || form.posted_at)">
          <div v-if="form.drafted_by || form.drafted_at">
            <i class="bi bi-pencil-square me-1 text-secondary"></i>
            Drafted<span v-if="form.drafted_by"> by <strong>{{ form.drafted_by }}</strong></span><span v-if="form.drafted_at"> on {{ fmtDateTime(form.drafted_at) }}</span>
          </div>
          <div v-if="form.posted_by || form.posted_at" class="mt-1">
            <i class="bi bi-check2-all me-1 text-success"></i>
            Posted<span v-if="form.posted_by"> by <strong>{{ form.posted_by }}</strong></span><span v-if="form.posted_at"> on {{ fmtDateTime(form.posted_at) }}</span>
          </div>
        </div>
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Journal entry saved successfully.</span>
        <div class="ms-auto d-flex gap-2">
          <button v-if="viewMode && form.status === 'draft'" type="button" class="btn btn-primary btn-lg px-4" @click="onPost" :disabled="posting">
            <span v-if="posting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <i v-else class="bi bi-check2-all me-2"></i>Post Entry
          </button>
          <button type="button" class="btn btn-success btn-lg px-5" @click="onSave" :disabled="!isReadyToSave || saving || viewMode">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <i v-else class="bi bi-check-lg me-2"></i>Save
          </button>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/journal_entries_search.php'; ?>
<?php include __DIR__ . '/coa_picker.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/accounting/journal_entries/journal_entries.js"></script>
