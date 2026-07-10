export default {
  url: '/home.php?page=driver_master_file',
  refSelector: '#dr-ref',
  refPrefix: 'DRV-',
  // Scalar fields only — employee_ref is appended in the spec since it depends
  // on a seeded FK row and is set via the employee picker modal, not fill().
  fields: [
    { selector: '#dr-name',    type: 'text',   value: 'Test Driver Playwright',  column: 'name' },
    { selector: '#dr-license', type: 'text',   value: 'LIC-TEST-001',            column: 'license_number' },
    { selector: '#dr-phone',   type: 'text',   value: '0799000000',              column: 'phone' },
    { selector: '#dr-status',  type: 'select', value: 'active',                  column: 'status' },
    { selector: '#dr-dob',     type: 'date',   value: '1990-01-15',              column: 'date_of_birth' },
    { selector: '#dr-join',    type: 'date',   value: '2024-03-01',              column: 'joining_date' },
  ],
  dbVerify: {
    table: 'm_drivers',
    column: 'license_number',
    value: 'LIC-TEST-001',
  },
}
