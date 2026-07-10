<?php /* entries/activity_log/activity_log.php — Activity & Export log viewer */ ?>

<div id="activity-log-app">

  <!-- ── Filter card ──────────────────────────────────────────────────────── -->
  <div class="card card-outline card-primary mb-3">
    <div class="card-body pb-2">
      <div class="row g-2 align-items-end">

        <!-- Log type -->
        <div class="col-sm-6 col-md-3 col-lg-2">
          <label class="form-label fw-semibold">Log Type</label>
          <select class="form-select" v-model="filters.table" @change="resetAndFetch">
            <option value="activity">System Log</option>
            <option value="export">Export Log</option>
          </select>
        </div>

        <!-- Date From -->
        <div class="col-sm-6 col-md-3 col-lg-2">
          <label class="form-label">Date From</label>
          <input type="date" class="form-control" v-model="filters.date_from" @change="resetAndFetch" />
        </div>

        <!-- Date To -->
        <div class="col-sm-6 col-md-3 col-lg-2">
          <label class="form-label">Date To</label>
          <input type="date" class="form-control" v-model="filters.date_to" @change="resetAndFetch" />
        </div>

        <!-- Action type (system log only) -->
        <div class="col-sm-6 col-md-3 col-lg-2" v-if="filters.table === 'activity'">
          <label class="form-label">Action</label>
          <select class="form-select" v-model="filters.action_type" @change="resetAndFetch">
            <option value="">All actions</option>
            <option value="login">Login</option>
            <option value="login_failed">Login Failed</option>
            <option value="logout">Logout</option>
            <option value="page_view">Page View</option>
            <option value="record_create">Record Create</option>
            <option value="record_save">Record Save</option>
            <option value="record_view">Record View</option>
            <option value="record_delete">Record Delete</option>
            <option value="record_print">Record Print</option>
            <option value="report_run">Report Run</option>
            <option value="report_print">Report Print</option>
            <option value="report_export_excel">Export Excel</option>
          </select>
        </div>

        <!-- Module (system log only) -->
        <div class="col-sm-6 col-md-3 col-lg-2" v-if="filters.table === 'activity'">
          <label class="form-label">Module</label>
          <input type="text" class="form-control" placeholder="e.g. driver_master_file"
                 v-model="filters.module" @keyup.enter="resetAndFetch" />
        </div>

        <!-- User ref -->
        <div class="col-sm-6 col-md-3 col-lg-2">
          <label class="form-label">User Ref</label>
          <input type="text" class="form-control" placeholder="e.g. USR-001"
                 v-model="filters.user_ref" @keyup.enter="resetAndFetch" />
        </div>

        <!-- Buttons -->
        <div class="col-sm-6 col-md-3 col-lg-2 d-flex gap-2">
          <button class="btn btn-primary flex-fill" @click="resetAndFetch" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-search me-1"></i>Search
          </button>
          <button class="btn btn-outline-secondary" @click="clearFilters" title="Clear filters">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ── Results card ──────────────────────────────────────────────────────── -->
  <div class="card card-outline card-secondary">
    <div class="card-header d-flex align-items-center gap-2">
      <span class="card-title me-auto">
        {{ filters.table === 'activity' ? 'System Log' : 'Export Log' }}
        <span v-if="total !== null" class="text-secondary fw-normal small ms-2">
          {{ total.toLocaleString() }} record{{ total !== 1 ? 's' : '' }}
        </span>
      </span>
      <span v-if="loading" class="spinner-border spinner-border-sm text-secondary"></span>
      <button type="button" class="btn btn-outline-secondary btn-sm module-help-btn" title="Help">
        <i class="bi bi-question-circle me-1"></i>Help
      </button>
    </div>
    <div class="card-body p-0">

      <!-- Empty state -->
      <div v-if="!loading && rows.length === 0" class="text-center text-secondary py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>No records found.
      </div>

      <!-- ── System Log table ──────────────────────────────────────────── -->
      <div v-if="filters.table === 'activity' && rows.length > 0" class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th>Date / Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Module</th>
              <th>Description</th>
              <th>Ref</th>
              <th class="text-end">IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.id">
              <td class="text-nowrap small">{{ fmtDate(r.created_at) }}</td>
              <td class="text-nowrap">
                <span class="fw-semibold">{{ r.user_ref || '—' }}</span>
                <br><span class="text-secondary small">{{ r.user_name || '' }}</span>
              </td>
              <td>
                <span :class="'badge ' + actionBadge(r.action_type)">{{ actionLabel(r.action_type) }}</span>
              </td>
              <td class="text-nowrap">
                <span v-if="r.module_label" class="small">{{ r.module_label }}</span>
                <span v-else class="text-secondary small">—</span>
                <br>
                <span v-if="r.module_section" class="text-secondary" style="font-size:0.72rem">{{ r.module_section }}</span>
              </td>
              <td class="small" style="max-width:260px">{{ r.description || '—' }}</td>
              <td class="small text-nowrap">{{ r.record_ref || '—' }}</td>
              <td class="text-end small text-secondary text-nowrap">{{ r.ip_address || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── Export Log table ──────────────────────────────────────────── -->
      <div v-if="filters.table === 'export' && rows.length > 0" class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th>Date / Time</th>
              <th>User</th>
              <th>Type</th>
              <th>Report</th>
              <th>Section</th>
              <th>Date Range</th>
              <th class="text-end">Rows</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.id">
              <td class="text-nowrap small">{{ fmtDate(r.created_at) }}</td>
              <td class="text-nowrap">
                <span class="fw-semibold">{{ r.user_ref || '—' }}</span>
                <br><span class="text-secondary small">{{ r.user_name || '' }}</span>
              </td>
              <td>
                <span :class="r.export_type === 'pdf' ? 'badge bg-danger' : 'badge bg-success'">
                  <i :class="r.export_type === 'pdf' ? 'bi bi-file-earmark-pdf me-1' : 'bi bi-file-earmark-excel me-1'"></i>
                  {{ r.export_type === 'pdf' ? 'PDF' : 'Excel' }}
                </span>
              </td>
              <td class="small">{{ r.report_name || r.report_system_name || '—' }}</td>
              <td class="small text-secondary">{{ r.module_section || '—' }}</td>
              <td class="small text-nowrap">
                <span v-if="r.date_from">{{ r.date_from }} → {{ r.date_to }}</span>
                <span v-else class="text-secondary">—</span>
              </td>
              <td class="text-end small">{{ r.row_count != null ? r.row_count.toLocaleString() : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <div class="card-footer d-flex align-items-center gap-3" v-if="rows.length > 0 || page > 1">
      <button class="btn btn-sm btn-outline-secondary" :disabled="page <= 1 || loading" @click="goPage(page - 1)">
        <i class="bi bi-chevron-left"></i> Prev
      </button>
      <span class="small text-secondary mx-auto">
        Page {{ page }}
        <span v-if="totalPages > 1"> of {{ totalPages }}</span>
      </span>
      <button class="btn btn-sm btn-outline-secondary" :disabled="!hasMore || loading" @click="goPage(page + 1)">
        Next <i class="bi bi-chevron-right"></i>
      </button>
    </div>

  </div>

</div>

<script>
(function () {
  const { createApp } = Vue;

  createApp({
    data() {
      const today = new Date().toISOString().slice(0, 10);
      const monthStart = today.slice(0, 8) + '01';
      return {
        filters: {
          table:       'activity',
          date_from:   monthStart,
          date_to:     today,
          action_type: '',
          module:      '',
          user_ref:    '',
        },
        rows:    [],
        total:   null,
        page:    1,
        limit:   50,
        loading: false,
      };
    },
    computed: {
      totalPages() { return this.total != null ? Math.ceil(this.total / this.limit) : null; },
      hasMore()    { return this.total == null || this.page * this.limit < this.total; },
    },
    mounted() { this.fetch(); },
    methods: {
      resetAndFetch() { this.page = 1; this.fetch(); },
      goPage(p)       { this.page = p; this.fetch(); },

      clearFilters() {
        const today      = new Date().toISOString().slice(0, 10);
        const monthStart = today.slice(0, 8) + '01';
        this.filters = { table: this.filters.table, date_from: monthStart, date_to: today,
                         action_type: '', module: '', user_ref: '' };
        this.resetAndFetch();
      },

      async fetch() {
        this.loading = true;
        try {
          const p = new URLSearchParams({
            table:       this.filters.table,
            date_from:   this.filters.date_from,
            date_to:     this.filters.date_to,
            action_type: this.filters.action_type,
            module:      this.filters.module,
            user_ref:    this.filters.user_ref,
            page:        this.page,
          });
          const { data } = await axios.get('activity_log_data.php?' + p);
          this.rows  = data.rows  || [];
          this.total = data.total ?? null;
        } catch (_) {
          this.rows = [];
        } finally {
          this.loading = false;
        }
      },

      fmtDate(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        return d.toLocaleDateString('en-LK', { day:'2-digit', month:'short', year:'numeric' })
             + ' ' + d.toLocaleTimeString('en-LK', { hour:'2-digit', minute:'2-digit', hour12: true });
      },

      actionLabel(t) {
        return {
          login:               'Login',
          login_failed:        'Login Failed',
          logout:              'Logout',
          page_view:           'Page View',
          record_create:       'Create',
          record_save:         'Save',
          record_view:         'View',
          record_delete:       'Delete',
          record_print:        'Print',
          report_run:          'Report Run',
          report_print:        'PDF Export',
          report_export_excel: 'Excel Export',
        }[t] || t;
      },

      actionBadge(t) {
        return {
          login:               'bg-success',
          login_failed:        'bg-danger',
          logout:              'bg-secondary',
          page_view:           'bg-info text-dark',
          record_create:       'bg-success',
          record_save:         'bg-primary',
          record_view:         'bg-info text-dark',
          record_delete:       'bg-danger',
          record_print:        'bg-warning text-dark',
          report_run:          'bg-primary',
          report_print:        'bg-warning text-dark',
          report_export_excel: 'bg-success',
        }[t] || 'bg-secondary';
      },
    },
  }).mount('#activity-log-app');
})();
</script>
