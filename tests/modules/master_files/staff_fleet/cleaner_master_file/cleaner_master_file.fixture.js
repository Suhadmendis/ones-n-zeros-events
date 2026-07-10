export default {
  url: '/home.php?page=cleaner_master_file',
  refSelector: '#cl-ref',
  refPrefix: 'CLN-',
  // Scalar fields only — employee_ref is appended in the spec since it depends
  // on a seeded FK row and is set via the employee picker modal, not fill().
  fields: [
    { selector: '#cl-name',   type: 'text',   value: 'Test Cleaner Playwright',  column: 'name' },
    { selector: '#cl-phone',  type: 'text',   value: '0799000001',               column: 'phone' },
    { selector: '#cl-status', type: 'select', value: 'active',                   column: 'status' },
    { selector: '#cl-dob',    type: 'date',   value: '1992-05-20',               column: 'date_of_birth' },
    { selector: '#cl-join',   type: 'date',   value: '2023-01-10',               column: 'joining_date' },
  ],
  dbVerify: {
    table: 'm_cleaners',
    column: 'phone',
    value: '0799000001',
  },
}
