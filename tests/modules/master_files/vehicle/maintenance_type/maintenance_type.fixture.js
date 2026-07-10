export default {
  url: '/home.php?page=maintenance_type',
  refSelector: '#maintenance-type-ref',
  refPrefix: 'MTY-',
  fields: [
    { selector: '#maintenance-type-code',                   type: 'text', value: 'TEST-MTY-CODE',            column: 'code' },
    { selector: '#maintenance-type-name',                   type: 'text', value: 'TEST Playwright Maintenance Type', column: 'name' },
    { selector: '#maintenance-type-description',            type: 'text', value: 'TEST description',         column: 'description' },
    { selector: '#maintenance-type-service-interval-km',    type: 'text', value: '10000',                    column: 'service_interval_km' },
    { selector: '#maintenance-type-service-interval-days',  type: 'text', value: '180',                      column: 'service_interval_days' },
  ],
  dbVerify: {
    table: 'm_maintenance_types',
    column: 'code',
    value: 'TEST-MTY-CODE',
  },
}
