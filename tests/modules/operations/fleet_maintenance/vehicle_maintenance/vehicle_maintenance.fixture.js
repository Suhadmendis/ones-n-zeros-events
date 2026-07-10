export default {
  url: '/home.php?page=vehicle_maintenance',
  refSelector: '#vehicle-maintenance-ref',
  refPrefix: 'VMT-',
  // Scalar fields only — vehicle_ref/maintenance_type_ref are appended in the
  // spec since they depend on FK rows (one seeded, one existing) per test.
  fields: [
    { selector: '#vehicle-maintenance-service-provider', type: 'text', value: 'TEST Playwright Garage', column: 'service_provider' },
    { selector: '#vehicle-maintenance-scheduled-date',   type: 'text', value: '2026-08-01',              column: 'scheduled_date' },
    { selector: '#vehicle-maintenance-start-date',       type: 'text', value: '2026-08-02',              column: 'start_date' },
    { selector: '#vehicle-maintenance-completed-date',   type: 'text', value: '2026-08-03',              column: 'completed_date' },
    { selector: '#vehicle-maintenance-odometer-km',      type: 'text', value: '45000',                   column: 'odometer_km' },
    { selector: '#vehicle-maintenance-labour-cost',      type: 'text', value: '5000',                    column: 'labour_cost' },
    { selector: '#vehicle-maintenance-parts-cost',       type: 'text', value: '8000',                    column: 'parts_cost' },
    { selector: '#vehicle-maintenance-total-cost',       type: 'text', value: '13000',                   column: 'total_cost' },
    { selector: '#vehicle-maintenance-remarks',          type: 'text', value: 'TEST Playwright remarks', column: 'remarks' },
  ],
  dbVerify: {
    table: 't_vehicle_maintenance',
    column: 'service_provider',
    value: 'TEST Playwright Garage',
  },
}
