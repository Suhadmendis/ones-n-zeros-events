export default {
  url: '/home.php?page=vehicle_model',
  refSelector: '#vehicle-model-ref',
  refPrefix: 'VMD-',
  // Scalar fields only — brand_ref/vehicle_type_ref/fuel_type_ref are appended
  // in the spec since they depend on FK rows seeded per-test (see spec.js).
  fields: [
    { selector: '#vehicle-model-code',                     type: 'text', value: 'TEST-VMD-CODE',            column: 'code' },
    { selector: '#vehicle-model-model-name',                type: 'text', value: 'TEST Playwright Model',    column: 'model_name' },
    { selector: '#vehicle-model-manufacture-start-year',     type: 'text', value: '2020',                    column: 'manufacture_start_year' },
    { selector: '#vehicle-model-manufacture-end-year',       type: 'text', value: '2024',                    column: 'manufacture_end_year' },
    { selector: '#vehicle-model-axle-count',                 type: 'text', value: '2',                       column: 'axle_count' },
    { selector: '#vehicle-model-seating-capacity',           type: 'text', value: '3',                       column: 'seating_capacity' },
    { selector: '#vehicle-model-payload-capacity-kg',        type: 'text', value: '1000',                    column: 'payload_capacity_kg' },
    { selector: '#vehicle-model-volume-capacity-m3',         type: 'text', value: '10',                      column: 'volume_capacity_m3' },
    { selector: '#vehicle-model-fuel-tank-capacity-l',       type: 'text', value: '80',                      column: 'fuel_tank_capacity_l' },
    { selector: '#vehicle-model-average-fuel-consumption',  type: 'text', value: '12.5',                    column: 'average_fuel_consumption' },
    { selector: '#vehicle-model-description',                type: 'text', value: 'TEST Playwright Description', column: 'description' },
  ],
  dbVerify: {
    table: 'm_vehicle_models',
    column: 'code',
    value: 'TEST-VMD-CODE',
  },
}
