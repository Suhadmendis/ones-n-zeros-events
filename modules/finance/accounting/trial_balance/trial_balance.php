<?php /* modules/finance/accounting/trial_balance/trial_balance.php */ ?>

<div id="tb-app">
  <div class="row g-0">
    <div class="col-12">
      <div class="card card-primary card-outline mb-4">

        <div class="card-header d-flex align-items-center gap-3">
          <div class="card-title fw-bold mb-0">Trial Balance</div>
          <div class="ms-auto d-flex align-items-center gap-2">
            <span class="badge text-bg-info">Accounting Report</span>
            <button type="button" class="btn btn-outline-secondary btn-sm module-help-btn" title="Help">
              <i class="bi bi-question-circle me-1"></i>Help
            </button>
          </div>
        </div>

        <div class="card-body">
          <p class="text-muted small mb-4">
            <i class="bi bi-info-circle me-1"></i>
            Select a date range and click <strong>Run Report</strong>. The trial balance will open in a new browser tab as a printable report.
          </p>

          <!-- Quick Period Shortcuts -->
          <div class="mb-4">
            <div class="small fw-semibold text-muted mb-2 text-uppercase" style="letter-spacing:.05em">Quick Period</div>
            <div class="d-flex flex-wrap gap-2">
              <button v-for="q in quickPeriods" :key="q.label"
                      class="btn btn-sm"
                      :class="activeQuick===q.label ? 'btn-primary' : 'btn-outline-secondary'"
                      @click="applyQuick(q)">
                {{ q.label }}
              </button>
            </div>
          </div>

          <!-- Criteria Form -->
          <div class="row g-3 align-items-end">
            <div class="col-sm-4 col-md-3">
              <label class="form-label small fw-semibold mb-1">Date From</label>
              <input type="date" class="form-control form-control-sm" v-model="dateFrom" @change="activeQuick=''" />
            </div>
            <div class="col-sm-4 col-md-3">
              <label class="form-label small fw-semibold mb-1">Date To</label>
              <input type="date" class="form-control form-control-sm" v-model="dateTo" @change="activeQuick=''" />
            </div>
            <div class="col-sm-4 col-md-3">
              <label class="form-label small fw-semibold mb-1">Basis</label>
              <select class="form-select form-select-sm" v-model="basis">
                <option value="accrual">Accrual (Posted entries only)</option>
                <option value="all">All Entries (incl. Draft)</option>
              </select>
            </div>
            <div class="col-sm-12 col-md-3 d-flex gap-2">
              <button class="btn btn-primary btn-sm flex-grow-1" @click="runReport" :disabled="!dateFrom || !dateTo">
                <i class="bi bi-play-fill me-1"></i>Run Report
              </button>
              <button class="btn btn-outline-secondary btn-sm" @click="clearCriteria" title="Clear">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>

          <!-- Validation message -->
          <div v-if="validationMsg" class="alert alert-warning d-flex align-items-center gap-2 mt-3 py-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ validationMsg }}</span>
          </div>

          <!-- Info card when criteria are set -->
          <div v-if="dateFrom && dateTo && !validationMsg" class="alert alert-secondary d-flex align-items-center gap-2 mt-3 py-2">
            <i class="bi bi-calendar3"></i>
            <span>
              Report period: <strong>{{ fmtDate(dateFrom) }}</strong> to <strong>{{ fmtDate(dateTo) }}</strong>
              &nbsp;&mdash;&nbsp;
              {{ basis === 'accrual' ? 'Posted entries only' : 'All entries including drafts' }}
            </span>
          </div>
        </div>

      </div>

      <!-- Help card -->
      <div class="card card-outline mb-4" style="border-color:#dee2e6">
        <div class="card-body py-3">
          <h6 class="fw-semibold mb-2"><i class="bi bi-book me-2 text-primary"></i>About the Trial Balance</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="small text-muted">
                <strong class="text-body">Opening Balance</strong><br>
                Net balance of all GL entries posted <em>before</em> the selected start date for each account.
              </div>
            </div>
            <div class="col-md-4">
              <div class="small text-muted">
                <strong class="text-body">Period Movement</strong><br>
                Total debits and credits posted within the selected date range.
              </div>
            </div>
            <div class="col-md-4">
              <div class="small text-muted">
                <strong class="text-body">Closing Balance</strong><br>
                Opening balance adjusted for period movements. Total debits must equal total credits.
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const { createApp } = Vue;
createApp({
  data() {
    const now   = new Date();
    const y     = now.getFullYear();
    const m     = now.getMonth() + 1;
    const firstDay = y + '-' + String(m).padStart(2,'0') + '-01';
    const lastDay  = new Date(y, m, 0);
    const lastStr  = y + '-' + String(m).padStart(2,'0') + '-' + String(lastDay.getDate()).padStart(2,'0');
    return {
      dateFrom:       firstDay,
      dateTo:         lastStr,
      basis:          'accrual',
      activeQuick:    '',
      quickPeriods:   [],
      validationMsg:  '',
    };
  },
  mounted() {
    this.buildQuickPeriods();
    this.activeQuick = this.quickPeriods[0]?.label || '';
  },
  methods: {
    buildQuickPeriods() {
      const now = new Date();
      const y   = now.getFullYear();
      const m   = now.getMonth();
      const MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const qs  = [];
      // Last 6 months
      for (let i = 0; i < 6; i++) {
        const d    = new Date(y, m - i, 1);
        const yr   = d.getFullYear();
        const mo   = d.getMonth();
        const last = new Date(yr, mo + 1, 0);
        qs.push({
          label:     MON[mo] + ' ' + yr,
          date_from: yr + '-' + String(mo+1).padStart(2,'0') + '-01',
          date_to:   yr + '-' + String(mo+1).padStart(2,'0') + '-' + String(last.getDate()).padStart(2,'0'),
        });
      }
      // FY current & prior
      qs.push({ label: 'FY ' + y,       date_from: y       + '-01-01', date_to: y       + '-12-31' });
      qs.push({ label: 'FY ' + (y - 1), date_from: (y - 1) + '-01-01', date_to: (y - 1) + '-12-31' });
      this.quickPeriods = qs;
    },
    applyQuick(q) {
      this.activeQuick = q.label;
      this.dateFrom    = q.date_from;
      this.dateTo      = q.date_to;
      this.validationMsg = '';
    },
    clearCriteria() {
      this.dateFrom      = '';
      this.dateTo        = '';
      this.activeQuick   = '';
      this.validationMsg = '';
    },
    runReport() {
      this.validationMsg = '';
      if (!this.dateFrom || !this.dateTo) {
        this.validationMsg = 'Please select both a start date and an end date.';
        return;
      }
      if (this.dateFrom > this.dateTo) {
        this.validationMsg = 'Date From must be on or before Date To.';
        return;
      }
      const params = new URLSearchParams({
        date_from: this.dateFrom,
        date_to:   this.dateTo,
        basis:     this.basis,
      });
      window.open(
        '/modules/finance/accounting/trial_balance/trial_balance_report.php?' + params.toString(),
        '_blank'
      );
    },
    fmtDate(v) {
      if (!v) return '';
      const [yr, mo, dy] = v.split('-');
      return dy + ' ' + ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][+mo - 1] + ' ' + yr;
    },
  },
}).mount('#tb-app');
</script>
