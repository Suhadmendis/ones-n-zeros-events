export default {
  url: '/home.php?page=location_master_file',
  refSelector: '#loc-ref',
  refPrefix: 'LOC-',
  fields: [
    { selector: '#loc-name',     type: 'text',   value: 'TEST-Playwright-Location', column: 'name' },
    { selector: '#loc-district', type: 'text',   value: 'Test District',           column: 'district' },
    { selector: '#loc-province', type: 'text',   value: 'Test Province',           column: 'province' },
    { selector: '#loc-status',   type: 'select', value: 'active',                  column: 'status' },
  ],
  dbVerify: {
    table: 'm_locations',
    column: 'name',
    value: 'TEST-Playwright-Location',
  },
}
