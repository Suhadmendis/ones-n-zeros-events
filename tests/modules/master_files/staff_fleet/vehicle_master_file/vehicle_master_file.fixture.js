export default {
  url: '/home.php?page=vehicle_master_file',
  refSelector: '#vh-ref',
  refPrefix: 'VEH-',
  fields: [
    { selector: '#vh-plate',    type: 'text',   value: 'PLT-TEST-001', column: 'plate_number' },
    { selector: '#vh-make',     type: 'text',   value: 'Toyota',       column: 'make' },
    { selector: '#vh-model',    type: 'text',   value: 'Dyna',         column: 'model' },
    { selector: '#vh-year',     type: 'text',   value: '2020',         column: 'year' },
    { selector: '#vh-capacity', type: 'text',   value: '5000',         column: 'capacity' },
    { selector: '#vh-status',   type: 'select', value: 'active',       column: 'status' },
    { selector: '#vh-type',     type: 'select', value: 'lorry',        column: 'type' },
    { selector: '#vh-fuel',     type: 'select', value: 'diesel',       column: 'fuel_type' },
    { selector: '#vh-mileage',  type: 'text',   value: '10000',        column: 'mileage' },
    { selector: '#vh-location', type: 'text',   value: 'Test Depot',   column: 'last_location' },
  ],
  dbVerify: {
    table: 'm_vehicles',
    column: 'plate_number',
    value: 'PLT-TEST-001',
  },
}
