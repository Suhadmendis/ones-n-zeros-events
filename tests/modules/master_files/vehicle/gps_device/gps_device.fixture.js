export default {
  url: '/home.php?page=gps_device',
  refSelector: '#gps-device-ref',
  refPrefix: 'GPS-',
  fields: [
    { selector: '#gps-device-code',          type: 'text', value: 'TEST-GPS-CODE',   column: 'code' },
    { selector: '#gps-device-serial-number', type: 'text', value: 'TEST-SN-000001',  column: 'serial_number' },
    { selector: '#gps-device-imei',          type: 'text', value: '356938035643809', column: 'imei' },
    { selector: '#gps-device-sim-number',    type: 'text', value: '+94771234567',    column: 'sim_number' },
    { selector: '#gps-device-manufacturer',  type: 'text', value: 'TEST Manufacturer', column: 'manufacturer' },
    { selector: '#gps-device-model',         type: 'text', value: 'TEST Model X',    column: 'model' },
  ],
  dbVerify: {
    table: 'm_gps_devices',
    column: 'code',
    value: 'TEST-GPS-CODE',
  },
}
