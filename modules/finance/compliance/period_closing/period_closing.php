<?php /* modules/finance/period_closing/period_closing.php — Period Closing form, included by home.php */ ?>

<div id="prc-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#prcSearchModal">Search</button>
            <button type="button" class="btn btn-secondary" @click="onEdit" :disabled="form.status === 'locked'">Edit</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button v-if="form.ref && form.status === 'open'" type="button" class="btn btn-outline-danger ms-2" @click="onClosePeriod">
            <i class="bi bi-lock me-1"></i>Close Period
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <!-- Status banner -->
        <div v-if="form.status === 'closed'" class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>This period is <strong>closed</strong>. No further posting allowed.</span>
        </div>
        <div v-if="form.status === 'locked'" class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
          <i class="bi bi-lock-fill"></i>
          <span>This period is <strong>locked</strong> and cannot be edited.</span>
        </div>

        <div class="row mt-2 g-4">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="prc-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="prc-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="prc-period" class="col-sm-4 col-form-label">Period</label>
              <div class="col-sm-5">
                <input type="month" class="form-control" id="prc-period" v-model="form.period" @change="syncPeriodName" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="prc-period-name" class="col-sm-4 col-form-label">Period Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="prc-period-name" placeholder="e.g. January 2025" v-model="form.period_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="prc-closing-date" class="col-sm-4 col-form-label">Closing Date</label>
              <div class="col-sm-6">
                <input type="date" class="form-control" id="prc-closing-date" v-model="form.closing_date" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="prc-notes" class="col-sm-4 col-form-label">Notes</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="prc-notes" rows="3" placeholder="Optional notes" v-model="form.notes"></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="prc-revenue" class="col-sm-4 col-form-label">Total Revenue</label>
              <div class="col-sm-7">
                <input type="number" class="form-control text-end" id="prc-revenue" min="0" step="0.01" placeholder="0.00" v-model="form.total_revenue" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="prc-expenses" class="col-sm-4 col-form-label">Total Expenses</label>
              <div class="col-sm-7">
                <input type="number" class="form-control text-end" id="prc-expenses" min="0" step="0.01" placeholder="0.00" v-model="form.total_expenses" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Net Profit</label>
              <div class="col-sm-7">
                <input type="text" class="form-control text-end font-monospace"
                  :class="netProfit >= 0 ? 'text-success' : 'text-danger'"
                  :value="netProfitFmt" disabled />
              </div>
            </div>
            <div class="row mb-3">
              <label for="prc-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-5">
                <select class="form-select" id="prc-status" v-model="form.status" :disabled="form.status === 'locked'">
                  <option value="open">Open</option>
                  <option value="closed">Closed</option>
                  <option value="locked">Locked</option>
                </select>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Period record saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave"
          :disabled="!isDirty || saving || form.status === 'locked'">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/period_closing_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/compliance/period_closing/period_closing.js"></script>
