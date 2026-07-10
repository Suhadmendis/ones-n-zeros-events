export default {
  url: '/home.php?page=vehicle_gps',
  refSelector: '#vehicle-gps-ref',
  refPrefix: 'VGP-',
  // Scalar fields only — vehicle_ref/gps_device_ref are appended in the spec
  // since they depend on FK rows (one seeded, one existing) per test.
  fields: [
    { selector: '#vehicle-gps-installed-date', type: 'text', value: '2026-07-01', column: 'installed_date' },
    { selector: '#vehicle-gps-removed-date',   type: 'text', value: '2026-07-15', column: 'removed_date' },
  ],
  dbVerify: {
    table: 't_vehicle_gps',
    column: 'installed_date',
    value: '2026-07-01',
  },
}
