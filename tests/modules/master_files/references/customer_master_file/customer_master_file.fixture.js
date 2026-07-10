export default {
  url: '/home.php?page=customer_master_file',
  refSelector: '#cu-ref',
  refPrefix: 'CUS-',
  fields: [
    { selector: '#cu-name',    type: 'text',   value: 'Test Customer Playwright',        column: 'customer_name' },
    { selector: '#cu-phone',   type: 'text',   value: '0799000002',                      column: 'phone' },
    { selector: '#cu-email',   type: 'text',   value: 'test.customer@playwright.test',   column: 'email' },
    { selector: '#cu-status',  type: 'select', value: 'active',                          column: 'record_status' },
    { selector: '#cu-address', type: 'text',   value: '123 Test Street, Test City',      column: 'address' },
  ],
  dbVerify: {
    table: 'm_customers',
    column: 'phone',
    value: '0799000002',
  },
}
