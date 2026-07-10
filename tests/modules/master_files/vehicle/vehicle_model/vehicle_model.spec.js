import { test, expect } from '@playwright/test'
import { VehicleModelPage } from './vehicle_model.page.js'
import { assertRecord, deleteRecord, seedRecord } from '../../../../helpers/db.js'
import fixture from './vehicle_model.fixture.js'

const uid = () => Math.random().toString(36).slice(2, 8).toUpperCase()

let brand, vehicleType, fuelType

test.beforeEach(async () => {
  await deleteRecord(fixture.dbVerify)
  brand       = await seedRecord('m_vehicle_brands', { ref: `TVBR${uid()}`, code: `TB${uid()}`, name: 'TEST Brand', status: 'active' })
  vehicleType = await seedRecord('m_vehicle_types',  { ref: `TVTY${uid()}`, code: `TT${uid()}`, name: 'TEST Type',  status: 'active' })
  fuelType    = await seedRecord('m_fuel_types',     { ref: `TFUE${uid()}`, code: `TF${uid()}`, name: 'TEST Fuel',  status: 'active' })
})

test.afterEach(async () => {
  await deleteRecord(fixture.dbVerify)
  await deleteRecord({ table: 'm_vehicle_brands', column: 'ref', value: brand.ref })
  await deleteRecord({ table: 'm_vehicle_types',  column: 'ref', value: vehicleType.ref })
  await deleteRecord({ table: 'm_fuel_types',     column: 'ref', value: fuelType.ref })
})

test('vehicle model: fill, save, and persist', async ({ page }) => {
  const form = new VehicleModelPage(page)
  await form.open()

  // FK dropdowns populate asynchronously after mount — wait for the seeded
  // options to appear before selecting them.
  await page.waitForSelector(`#vehicle-model-brand-ref option[value="${brand.ref}"]`)
  await page.waitForSelector(`#vehicle-model-vehicle-type-ref option[value="${vehicleType.ref}"]`)
  await page.waitForSelector(`#vehicle-model-fuel-type-ref option[value="${fuelType.ref}"]`)

  const fields = [
    ...fixture.fields,
    { selector: '#vehicle-model-brand-ref',        type: 'select', value: brand.ref,       column: 'brand_ref' },
    { selector: '#vehicle-model-vehicle-type-ref',  type: 'select', value: vehicleType.ref, column: 'vehicle_type_ref' },
    { selector: '#vehicle-model-fuel-type-ref',     type: 'select', value: fuelType.ref,    column: 'fuel_type_ref' },
  ]

  await form.fill(fields)
  const saved = await form.submit()
  for (const field of fields) {
    expect(String(saved[field.column] ?? ''), `response.${field.column}`).toBe(String(field.value))
  }
  await assertRecord(fixture.dbVerify, fields)
})
