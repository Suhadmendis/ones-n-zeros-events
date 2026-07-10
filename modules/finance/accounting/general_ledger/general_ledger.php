<?php /* modules/finance/general_ledger/general_ledger.php — General Ledger viewer */ ?>

<style>
#gl-app .gl-row-dr  { background:rgba(25,135,84,.04); }
#gl-app .gl-row-cr  { background:rgba(220,53,69,.04); }
#gl-app .gl-row-dr:hover { background:rgba(25,135,84,.11) !important; }
#gl-app .gl-row-cr:hover { background:rgba(220,53,69,.11) !important; }
#gl-app .gl-thead th { background:#1a1a2e; color:#fff; font-weight:500; position:sticky; top:0; z-index:10; font-size:0.78rem; }
#gl-app .gl-table    { font-size:0.82rem; }
#gl-app .gl-stat-card { background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:14px 16px; display:flex; align-items:center; gap:14px; height:100%; }
#gl-app .gl-stat-icon { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.2rem; }
</style>

<div id="gl-app" class="row g-0">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">

      <!-- Header -->
      <div class="card-header d-flex align-items-center gap-3">
        <div class="card-title fw-bold mb-0">{{ title }}</div>
        <div class="ms-auto d-flex gap-2">
          <button class="btn btn-sm btn-outline-success" @click="exportCSV" :disabled="!entries.length">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV
          </button>
          <button class="btn btn-sm btn-outline-secondary" @click="printView" :disabled="!entries.length">
            <i class="bi bi-printer me-1"></i>Print
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>
      </div>

      <!-- Filter Panel -->
      <div class="card-body border-bottom" style="background:#f8f9fa">
        <div class="row g-2 mb-2">

          <!-- Account Code with typeahead -->
          <div class="col-6 col-md-2" style="position:relative">
            <label class="form-label small fw-semibold mb-1">Account Code</label>
            <input type="text" class="form-control form-control-sm font-monospace"
                   v-model="filters.account_code"
                   @input="onAccountInput"
                   @blur="closeAccountSugg"
                   @keydown.esc="closeAccountSugg"
                   @keydown.down.prevent="acctNav('down')"
                   @keydown.up.prevent="acctNav('up')"
                   @keydown.enter.prevent="acctEnter"
                   placeholder="e.g. 1100" autocomplete="off" />
            <ul v-if="acctSugg.length"
                class="list-group position-absolute shadow"
                style="z-index:9999;top:100%;left:0;right:0;max-height:220px;overflow-y:auto">
              <li v-for="(s,j) in acctSugg" :key="s.account_code"
                  class="list-group-item list-group-item-action py-1 px-2 small"
                  :class="{active:j===acctIdx}"
                  @mousedown.prevent="pickAcct(s)">
                <span class="font-monospace fw-semibold me-2">{{ s.account_code }}</span>
                <span :class="{'text-muted':j!==acctIdx}">{{ s.account_name }}</span>
              </li>
            </ul>
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Account Name</label>
            <input type="text" class="form-control form-control-sm" v-model="filters.account_name_display"
                   readonly placeholder="Auto-filled" style="background:#fff;cursor:default;color:#555" />
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <input type="month" class="form-control form-control-sm" v-model="filters.period" @change="onPeriodChange" />
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Date From</label>
            <input type="date" class="form-control form-control-sm" v-model="filters.date_from" @change="onDateChange" />
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Date To</label>
            <input type="date" class="form-control form-control-sm" v-model="filters.date_to" @change="onDateChange" />
          </div>

        </div>

        <div class="row g-2 align-items-end">

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Journal Ref</label>
            <input type="text" class="form-control form-control-sm font-monospace"
                   v-model="filters.journal_ref" placeholder="JNL-0000001" />
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select class="form-select form-select-sm" v-model="filters.status">
              <option value="">All Statuses</option>
              <option value="posted">Posted</option>
              <option value="draft">Draft</option>
              <option value="reversed">Reversed</option>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold mb-1">Search Description</label>
            <input type="text" class="form-control form-control-sm"
                   v-model="filters.description" placeholder="Type to search narration…"
                   @keydown.enter="applyFilters" />
          </div>

          <div class="col-12 col-md-4 d-flex gap-2">
            <button class="btn btn-primary btn-sm px-4 flex-grow-1" @click="applyFilters">
              <i class="bi bi-funnel-fill me-1"></i>Apply Filters
            </button>
            <button class="btn btn-outline-secondary btn-sm px-3" @click="clearFilters" title="Clear all filters">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

        </div>

        <!-- Quick period shortcuts -->
        <div class="mt-3 d-flex flex-wrap align-items-center gap-1">
          <span class="text-muted small me-1 fw-semibold">Quick:</span>
          <button v-for="q in quickPeriods" :key="q.label"
                  class="btn btn-sm py-0 px-2"
                  :class="activeQuick === q.label ? 'btn-primary' : 'btn-outline-secondary'"
                  style="font-size:0.72rem;line-height:1.7"
                  @click="applyQuick(q)">{{ q.label }}</button>
        </div>
      </div>

      <!-- Summary stats -->
      <div class="card-body border-bottom py-3">
        <div class="row g-3">
          <div class="col-6 col-md-3">
            <div class="gl-stat-card">
              <div class="gl-stat-icon" style="background:rgba(13,110,253,.1)">
                <i class="bi bi-journal-bookmark-fill text-primary"></i>
              </div>
              <div>
                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em">Entries</div>
                <div class="fw-bold fs-5 lh-1 mt-1">{{ fmtCount(totals.total_count) }}</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="gl-stat-card">
              <div class="gl-stat-icon" style="background:rgba(25,135,84,.1)">
                <i class="bi bi-arrow-up-right-circle-fill text-success"></i>
              </div>
              <div class="min-w-0 overflow-hidden">
                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em">Total Debits</div>
                <div class="fw-bold lh-1 mt-1 text-success font-monospace text-truncate" style="font-size:0.95rem">{{ fmtNum(totals.total_debit) }}</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="gl-stat-card">
              <div class="gl-stat-icon" style="background:rgba(220,53,69,.1)">
                <i class="bi bi-arrow-down-right-circle-fill text-danger"></i>
              </div>
              <div class="min-w-0 overflow-hidden">
                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em">Total Credits</div>
                <div class="fw-bold lh-1 mt-1 text-danger font-monospace text-truncate" style="font-size:0.95rem">{{ fmtNum(totals.total_credit) }}</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="gl-stat-card">
              <div class="gl-stat-icon" style="background:rgba(13,202,240,.1)">
                <i class="bi bi-calculator-fill text-info"></i>
              </div>
              <div class="min-w-0 overflow-hidden">
                <div class="text-muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.05em">Net Balance</div>
                <div class="fw-bold lh-1 mt-1 font-monospace text-truncate"
                     :class="netBalance >= 0 ? 'text-success' : 'text-danger'"
                     style="font-size:0.95rem">
                  {{ fmtNum(Math.abs(netBalance)) }}
                  <span style="font-size:0.72rem;font-weight:400" class="ms-1">{{ netBalance >= 0 ? 'Dr' : 'Cr' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table toolbar -->
      <div class="card-body py-2 border-bottom d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted small" v-if="searched && totals.total_count > 0">
          Showing <strong>{{ rangeStart.toLocaleString() }}–{{ rangeEnd.toLocaleString() }}</strong>
          of <strong>{{ totals.total_count.toLocaleString() }}</strong> entries
        </span>
        <span class="text-muted small" v-else-if="searched">No entries found</span>
        <span class="text-muted small" v-else>Use filters above to view entries</span>

        <div class="ms-auto d-flex align-items-center gap-2">
          <span class="text-muted small">Rows per page:</span>
          <select class="form-select form-select-sm" style="width:75px" v-model.number="perPage" @change="applyFilters">
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
            <option :value="200">200</option>
          </select>
        </div>
      </div>

      <!-- Table area -->
      <div class="card-body p-0">

        <!-- Loading -->
        <div v-if="loading" class="py-5 text-center text-muted">
          <div class="spinner-border text-primary mb-3" role="status" style="width:2rem;height:2rem"></div>
          <div class="small">Loading ledger entries…</div>
        </div>

        <!-- Empty after search -->
        <div v-else-if="searched && !entries.length" class="py-5 text-center text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
          <div class="fw-semibold">No entries match your filters</div>
          <div class="small mt-1">Try a different account, period, or clear the filters.</div>
        </div>

        <!-- Initial prompt -->
        <div v-else-if="!searched" class="py-5 text-center text-muted">
          <i class="bi bi-funnel fs-1 d-block mb-3 opacity-25"></i>
          <div class="fw-semibold fs-6">Select filters to view General Ledger entries</div>
          <div class="small mt-2">Filter by account, period, date range, journal ref, or use a quick shortcut above.</div>
        </div>

        <!-- Main table -->
        <div v-else>
          <div class="table-responsive">
            <table class="table table-hover table-bordered gl-table mb-0 align-middle">
              <thead class="gl-thead">
                <tr>
                  <th class="px-3 py-2 text-nowrap" style="width:92px">Date</th>
                  <th class="px-2 py-2" style="width:108px">GL Ref</th>
                  <th class="px-2 py-2" style="width:118px">Journal Ref</th>
                  <th class="px-2 py-2" style="width:62px">Code</th>
                  <th class="px-2 py-2" style="min-width:140px">Account Name</th>
                  <th class="px-2 py-2">Description</th>
                  <th class="px-2 py-2 text-end" style="width:132px">Debit (LKR)</th>
                  <th class="px-2 py-2 text-end" style="width:132px">Credit (LKR)</th>
                  <th class="px-2 py-2 text-end" style="width:132px" v-if="showBalance">Balance</th>
                  <th class="px-2 py-2 text-center" style="width:76px">Status</th>
                </tr>
              </thead>
              <tbody>
                <!-- Opening balance row (pages > 1, single account) -->
                <tr v-if="showBalance && page > 1"
                    style="background:#eef6fb;font-size:0.76rem;border-top:2px solid #b8d8ea">
                  <td colspan="6" class="px-3 py-1 text-end text-muted fst-italic fw-semibold">
                    <i class="bi bi-arrow-return-right me-1"></i>Opening Balance (b/f from previous page)
                  </td>
                  <td class="px-2 py-1 text-end font-monospace fw-semibold text-success">
                    {{ openingBalance > 0 ? fmtNum(openingBalance) : '' }}
                  </td>
                  <td class="px-2 py-1 text-end font-monospace fw-semibold text-danger">
                    {{ openingBalance < 0 ? fmtNum(Math.abs(openingBalance)) : '' }}
                  </td>
                  <td class="px-2 py-1 text-end font-monospace fw-bold"
                      :class="openingBalance >= 0 ? 'text-success' : 'text-danger'">
                    {{ fmtNum(Math.abs(openingBalance)) }}
                    <span style="font-size:0.65rem;opacity:.7">{{ openingBalance >= 0 ? 'Dr' : 'Cr' }}</span>
                  </td>
                  <td class="py-1"></td>
                </tr>

                <!-- Data rows -->
                <tr v-for="(row, i) in entriesWithBalance" :key="row.ref"
                    :class="parseFloat(row.debit_amount) > 0 ? 'gl-row-dr' : 'gl-row-cr'">
                  <td class="px-3 text-nowrap">{{ fmtDate(row.transaction_date) }}</td>
                  <td class="px-2 font-monospace text-muted" style="font-size:0.76rem">{{ row.ref }}</td>
                  <td class="px-2 font-monospace">
                    <span class="text-primary fw-semibold"
                          style="cursor:pointer;text-decoration:underline;text-decoration-style:dotted;text-underline-offset:2px"
                          :title="'Journal Entry: ' + row.journal_ref"
                          @click="jumpToJE(row.journal_ref)">{{ row.journal_ref }}</span>
                  </td>
                  <td class="px-2 font-monospace fw-bold text-dark">{{ row.account_code }}</td>
                  <td class="px-2">{{ row.account_name }}</td>
                  <td class="px-2 text-truncate" style="max-width:240px" :title="row.description">{{ row.description }}</td>
                  <td class="px-2 text-end font-monospace fw-semibold"
                      :class="parseFloat(row.debit_amount) > 0 ? 'text-success' : 'text-black-50'">
                    {{ parseFloat(row.debit_amount) > 0 ? fmtNum(row.debit_amount) : '—' }}
                  </td>
                  <td class="px-2 text-end font-monospace fw-semibold"
                      :class="parseFloat(row.credit_amount) > 0 ? 'text-danger' : 'text-black-50'">
                    {{ parseFloat(row.credit_amount) > 0 ? fmtNum(row.credit_amount) : '—' }}
                  </td>
                  <td class="px-2 text-end font-monospace" v-if="showBalance"
                      :class="row._balance >= 0 ? 'text-success' : 'text-danger'">
                    {{ fmtNum(Math.abs(row._balance)) }}
                    <span style="font-size:0.65rem;opacity:.7">{{ row._balance >= 0 ? 'Dr' : 'Cr' }}</span>
                  </td>
                  <td class="px-2 text-center">
                    <span class="badge rounded-pill" :class="statusBadge(row.status)">{{ row.status }}</span>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr style="background:#f0f2f5;border-top:2px solid #ced4da">
                  <td colspan="6" class="px-3 py-2 text-end text-muted fw-semibold" style="font-size:0.76rem">
                    Page Subtotal ({{ entries.length }} rows)
                  </td>
                  <td class="px-2 py-2 text-end font-monospace fw-bold text-success">{{ fmtNum(pageTotalDebit) }}</td>
                  <td class="px-2 py-2 text-end font-monospace fw-bold text-danger">{{ fmtNum(pageTotalCredit) }}</td>
                  <td v-if="showBalance" class="px-2 py-2 text-end font-monospace fw-bold"
                      :class="(pageTotalDebit - pageTotalCredit) >= 0 ? 'text-success' : 'text-danger'">
                    {{ fmtNum(Math.abs(pageTotalDebit - pageTotalCredit)) }}
                    <span style="font-size:0.65rem;font-weight:400">{{ (pageTotalDebit-pageTotalCredit) >= 0 ? 'Dr':'Cr' }}</span>
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <button class="btn btn-sm btn-outline-secondary" :disabled="page <= 1" @click="goPage(page - 1)">
              <i class="bi bi-chevron-left"></i> Prev
            </button>
            <div class="d-flex gap-1 flex-wrap justify-content-center">
              <template v-for="p in paginationPages" :key="'pg-' + p">
                <span v-if="p === '…'" class="btn btn-sm px-2 disabled opacity-50">…</span>
                <button v-else class="btn btn-sm"
                        :class="p === page ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="goPage(p)">{{ p }}</button>
              </template>
            </div>
            <button class="btn btn-sm btn-outline-secondary" :disabled="page >= totalPages" @click="goPage(page + 1)">
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/finance/accounting/general_ledger/general_ledger.js"></script>
