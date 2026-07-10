(() => {
  'use strict';

  // User context is injected by PHP into window.__LOG_USER__
  const _u = window.__LOG_USER__ || {};

  // ── Module section map (system_name → section label) ─────────────────────
  const SECTION_MAP = {
    // Company Management
    company_profile: 'Company Management', create_user: 'Company Management',
    user_permissions: 'Company Management', password_change: 'Company Management',
    edit_deactivate_user: 'Company Management', activity_log: 'Company Management',

    // Master Files
    driver_master_file: 'Master Files', vehicle_master_file: 'Master Files',
    cleaner_master_file: 'Master Files', customer_master_file: 'Master Files',
    item_master_file: 'Master Files', location_master_file: 'Master Files',
    general_expense_type: 'Master Files',

    // Operations
    trip_running_chart: 'Operations',
    fuel_entry: 'Operations', vehicle_expense_entry: 'Operations',
    driver_advance: 'Operations', deduction: 'Operations', loan: 'Operations',
    payment_salary_disburse: 'Operations', driver_salary_settlement: 'Operations',
    cleaner_salary_settlement: 'Operations', cash_flow: 'Operations',
    general_expense_entry: 'Operations',

    // Reports
    all_fuel_list: 'Reports', business_summary_dashboard: 'Reports',
    cash_flow_print: 'Reports', cleaner_salary_list: 'Reports',
    driver_income_report: 'Reports', driver_salary_list: 'Reports',
    driver_salary_summary: 'Reports', fuel_usage_report: 'Reports',
    general_expenses_report: 'Reports', lorry_vs_trips_report: 'Reports',
    monthly_income_summary: 'Reports', running_chart_full_ledger: 'Reports',
    trip_expense_report: 'Reports', vehicle_expenses_report: 'Reports',
    vehicle_income_report: 'Reports', vehicle_profitability_report: 'Reports',

    // Executive Reports
    business_performance_summary: 'Executive Reports',
    daily_business_snapshot: 'Executive Reports',

    // Financial Reports
    cash_flow_statement: 'Financial Reports', expense_summary: 'Financial Reports',
    expense_trend: 'Financial Reports', income_vs_expense: 'Financial Reports',
    monthly_profit: 'Financial Reports', revenue_trend: 'Financial Reports',

    // Fleet Reports
    fuel_cost_trend: 'Fleet Reports', fuel_efficiency: 'Fleet Reports',
    mileage_report: 'Fleet Reports', most_expensive_vehicle: 'Fleet Reports',
    vehicle_expense_analysis: 'Fleet Reports', vehicle_performance: 'Fleet Reports',
    vehicle_utilization: 'Fleet Reports',

    // Operations Reports
    department_revenue: 'Operations Reports', item_type_revenue: 'Operations Reports',
    route_profitability: 'Operations Reports', trip_frequency: 'Operations Reports',
    trip_revenue: 'Operations Reports',

    // Payroll Reports
    my_advances: 'Payroll Reports', my_earnings: 'Payroll Reports',
    my_loan_status: 'Payroll Reports', my_salary_statement: 'Payroll Reports',
    my_trips: 'Payroll Reports',

    // Staff Reports
    cleaner_advance_report: 'Staff Reports', cleaner_loan_report: 'Staff Reports',
    cleaner_performance: 'Staff Reports', cleaner_salary_report: 'Staff Reports',
    driver_advance_report: 'Staff Reports', driver_deduction_report: 'Staff Reports',
    driver_earnings: 'Staff Reports', driver_loan_report: 'Staff Reports',
    driver_performance: 'Staff Reports', driver_salary_sheet: 'Staff Reports',

    // Analytics Reports
    highest_expense_vehicle: 'Analytics Reports', lowest_fuel_efficiency: 'Analytics Reports',
    profit_per_km: 'Analytics Reports', top_performing_driver: 'Analytics Reports',
    top_performing_vehicle: 'Analytics Reports',
  };

  function _label(system_name) {
    return (system_name || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }
  function _section(system_name) {
    return SECTION_MAP[system_name] || 'Other';
  }
  function _base(override = {}) {
    return {
      user_id:   _u.id   || null,
      user_ref:  _u.ref  || null,
      user_name: _u.name || null,
      user_role: _u.role || null,
      ...override,
    };
  }

  // ── Public API ────────────────────────────────────────────────────────────
  window.ActivityLogger = {

    // Generic log entry — fire and forget
    log(action_type, opts = {}) {
      if (typeof db === 'undefined') return;
      db.from('user_activity_log').insert({
        ..._base(),
        action_type,
        module:         opts.module         || null,
        module_section: opts.module_section || null,
        module_label:   opts.module_label   || null,
        record_ref:     opts.record_ref     || null,
        description:    opts.description    || null,
        metadata:       opts.metadata       || {},
      }).then(() => {}).catch(() => {});
    },

    // Export/print — logs to both tables
    async logExport(export_type, opts = {}) {
      if (typeof db === 'undefined') return;
      try {
        const action = export_type === 'pdf' ? 'report_print' : 'report_export_excel';
        const { data: rows, error } = await db
          .from('user_activity_log')
          .insert({
            ..._base(),
            action_type:    action,
            module:         opts.module         || null,
            module_section: opts.module_section || null,
            module_label:   opts.module_label   || null,
            description:    opts.description    || null,
            metadata:       opts.metadata       || {},
          })
          .select('id');

        if (error) return;
        const log_id = rows?.[0]?.id || null;

        await db.from('export_records').insert({
          log_id,
          user_id:            _u.id   || null,
          user_ref:           _u.ref  || null,
          user_name:          _u.name || null,
          export_type,
          report_name:        opts.module_label       || null,
          report_system_name: opts.module             || null,
          module_section:     opts.module_section     || null,
          date_from:          opts.date_from          || null,
          date_to:            opts.date_to            || null,
          filter_params:      opts.filter_params      || {},
          row_count:          opts.row_count != null ? opts.row_count : null,
        });
      } catch (_) {}
    },
  };

  // ── Axios interceptor — log entry saves + report runs ────────────────────
  if (typeof axios !== 'undefined') {
    axios.interceptors.response.use(
      response => {
        try {
          const cfg    = response.config || {};
          const url    = cfg.url || '';
          const method = (cfg.method || '').toLowerCase();
          const qs     = new URLSearchParams(url.split('?')[1] || '');
          const action = qs.get('action') || '';

          // ── Record save (create / edit) ──────────────────────────────
          if (method === 'post' && action === 'save' && url.includes('_data.php')) {
            const module = url.replace(/.*\/entries\//, '').replace(/\/.*/, '');
            let body = {};
            try { body = JSON.parse(cfg.data || '{}'); } catch (_) {}
            const isNew = !!(response.data?.created || response.data?.inserted);
            const act   = isNew ? 'record_create' : 'record_save';
            ActivityLogger.log(act, {
              module,
              module_section: _section(module),
              module_label:   _label(module),
              record_ref:     body.ref || null,
              description:    `${isNew ? 'Created' : 'Saved'} ${_label(module)}${body.ref ? ': ' + body.ref : ''}`,
              metadata:       { ref: body.ref || null },
            });
          }

          // ── Report run ────────────────────────────────────────────────
          if (method === 'get' && action === 'report' && url.includes('_data.php')) {
            const module   = url.replace(/.*\/reports\//, '').replace(/\/.*/, '');
            const from     = qs.get('from') || qs.get('date_from') || null;
            const to       = qs.get('to')   || qs.get('date_to')   || null;
            const rowCount = Array.isArray(response.data?.rows)
                             ? response.data.rows.length
                             : (Array.isArray(response.data) ? response.data.length : null);
            ActivityLogger.log('report_run', {
              module,
              module_section: _section(module),
              module_label:   _label(module),
              description:    `Ran ${_label(module)}${from ? ' (' + from + ' → ' + to + ')' : ''}`,
              metadata: {
                date_from:  from,
                date_to:    to,
                row_count:  rowCount,
                filters:    Object.fromEntries(qs),
              },
            });
          }

          // ── Record delete ─────────────────────────────────────────────
          if (method === 'post' && action === 'delete' && url.includes('_data.php')) {
            const module = url.replace(/.*\/entries\//, '').replace(/\/.*/, '');
            let body = {};
            try { body = JSON.parse(cfg.data || '{}'); } catch (_) {}
            ActivityLogger.log('record_delete', {
              module,
              module_section: _section(module),
              module_label:   _label(module),
              record_ref:     body.ref || null,
              description:    `Deleted ${_label(module)}${body.ref ? ': ' + body.ref : ''}`,
              metadata:       { ref: body.ref || null },
            });
          }

          // ── Record print (entry print window opened) ──────────────────
          if (method === 'get' && action === 'exists' && url.includes('_data.php')) {
            if (response.data?.exists) {
              const module = url.replace(/.*\/entries\//, '').replace(/\/.*/, '');
              const ref    = qs.get('ref') || null;
              ActivityLogger.log('record_print', {
                module,
                module_section: _section(module),
                module_label:   _label(module),
                record_ref:     ref,
                description:    `Printed ${_label(module)}${ref ? ': ' + ref : ''}`,
                metadata:       { ref },
              });
            }
          }
        } catch (_) {}
        return response;
      },
      err => Promise.reject(err)
    );
  }

  // ── Record view — fired when search modal row is clicked ─────────────────
  document.addEventListener('DOMContentLoaded', () => {

    // Maps every CustomEvent name → the module whose record was selected
    const EVENT_MODULE_MAP = {
      // Master file direct selections
      'driver-selected':              'driver_master_file',
      'vehicle-selected':             'vehicle_master_file',
      'cleaner-selected':             'cleaner_master_file',
      'customer-selected':            'customer_master_file',
      'item-selected':                'item_master_file',
      'location-selected':            'location_master_file',
      'user-selected':                'create_user',
      'edit-user-selected':           'edit_deactivate_user',
      'up-user-selected':             'user_permissions',
      'password-change-user-selected':'password_change',
      'expense-type-selected':        'general_expense_type',

      // Entry record selections (loading an existing record into a form)
      'cash-flow-selected':           'cash_flow',
      'cleaner-settlement-selected':  'cleaner_salary_settlement',
      'deduction-selected':           'deduction',
      'driver-settlement-selected':   'driver_salary_settlement',
      'fuel-entry-selected':          'fuel_entry',
      'gex-entry-selected':           'general_expense_entry',
      'trip-running-chart-selected':  'trip_running_chart',
      'vex-entry-selected':           'vehicle_expense_entry',
      'adv-entry-selected':           'driver_advance',

      // Related-record lookups within forms (driver/vehicle/etc picked inside another form)
      'adv-driver-selected':          'driver_master_file',
      'adv-cleaner-selected':         'cleaner_master_file',
      'deduction-driver-selected':    'driver_master_file',
      'deduction-cleaner-selected':   'cleaner_master_file',
      'fuel-vehicle-selected':        'vehicle_master_file',
      'gex-expense-type-selected':    'general_expense_type',
      'trip-vehicle-selected':        'vehicle_master_file',
      'trip-driver-selected':         'driver_master_file',
      'trip-cleaner-selected':        'cleaner_master_file',
      'trip-item-selected':           'item_master_file',
      'vex-vehicle-selected':         'vehicle_master_file',
      'loan-driver-selected':         'driver_master_file',
      'advance-driver-selected':      'driver_master_file',
      'settlement-driver-selected':   'driver_master_file',
      'payment-driver-selected':      'driver_master_file',
    };

    Object.entries(EVENT_MODULE_MAP).forEach(([evName, module]) => {
      document.addEventListener(evName, e => {
        const detail = e.detail || {};
        ActivityLogger.log('record_view', {
          module,
          module_section: _section(module),
          module_label:   _label(module),
          record_ref:     detail.ref || null,
          description:    `Viewed ${_label(module)}${detail.ref ? ': ' + detail.ref : ''}`,
          metadata: {
            ref:          detail.ref          || null,
            name:         detail.name         || detail.plate_number || null,
            source_event: evName,
          },
        });
      });
    });
  });

})();
