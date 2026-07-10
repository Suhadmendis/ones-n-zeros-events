export default {
  url: '/home.php?page=general_expense_type',
  refSelector: '#get-ref',
  refPrefix: 'GET-',
  fields: [
    { selector: '#get-name',   type: 'text',   value: 'TEST-Playwright-Expense', column: 'name' },
    { selector: '#get-status', type: 'select', value: 'active',                  column: 'status' },
  ],
  dbVerify: {
    table: 'm_general_expense_types',
    column: 'name',
    value: 'TEST-Playwright-Expense',
  },
}
