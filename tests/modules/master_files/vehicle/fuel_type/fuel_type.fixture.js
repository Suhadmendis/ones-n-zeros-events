export default {
  url: '/home.php?page=fuel_type',
  refSelector: '#fuel-type-ref',
  refPrefix: 'FUE-',
  fields: [
    { selector: '#fuel-type-code', type: 'text', value: 'TEST-FUE-CODE',        column: 'code' },
    { selector: '#fuel-type-name', type: 'text', value: 'TEST Playwright Fuel', column: 'name' },
  ],
  dbVerify: {
    table: 'm_fuel_types',
    column: 'code',
    value: 'TEST-FUE-CODE',
  },
}
