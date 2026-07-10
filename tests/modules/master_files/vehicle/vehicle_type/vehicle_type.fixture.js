export default {
  url: '/home.php?page=vehicle_type',
  refSelector: '#vehicle-type-ref',
  refPrefix: 'VTY-',
  fields: [
    { selector: '#vehicle-type-code',        type: 'text', value: 'TEST-VTY-CODE',         column: 'code' },
    { selector: '#vehicle-type-name',        type: 'text', value: 'TEST Playwright Type',  column: 'name' },
    { selector: '#vehicle-type-description', type: 'text', value: 'TEST type description', column: 'description' },
  ],
  dbVerify: {
    table: 'm_vehicle_types',
    column: 'code',
    value: 'TEST-VTY-CODE',
  },
}
