export default {
  url: '/home.php?page=vehicle_brand',
  refSelector: '#vehicle-brand-ref',
  refPrefix: 'VBR-',
  fields: [
    { selector: '#vehicle-brand-code',    type: 'text', value: 'TEST-VBR-CODE',       column: 'code' },
    { selector: '#vehicle-brand-name',    type: 'text', value: 'TEST Playwright Brand', column: 'name' },
    { selector: '#vehicle-brand-country', type: 'text', value: 'Testland',            column: 'country' },
  ],
  dbVerify: {
    table: 'm_vehicle_brands',
    column: 'code',
    value: 'TEST-VBR-CODE',
  },
}
