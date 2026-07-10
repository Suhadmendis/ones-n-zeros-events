import { test, expect } from '@playwright/test'
import { VehicleMaintenancePage } from './vehicle_maintenance.page.js'
import { assertRecord, deleteRecord, seedRecord, getAnyRecord } from '../../../../helpers/db.js'
import fixture from './vehicle_maintenance.fixture.js'

const uid = () => Math.random().toString(36).slice(2, 8).toUpperCase()

let vehicle, maintenanceType

test.beforeEach(async () => {
  await deleteRecord(fixture.dbVerify)
  vehicle         = await getAnyRecord('m_vehicles')
  maintenanceType = await seedRecord('m_maintenance_types', { ref: `TMTY${uid()}`, code: `TM${uid()}`, name: 'TEST Maintenance Type', status: 'active' })
})

test.afterEach(async () => {
  await deleteRecord(fixture.dbVerify)
  await deleteRecord({ table: 'm_maintenance_types', column: 'ref', value: maintenanceType.ref })
})

test('vehicle maintenance: fill, save, and persist', async ({ page }) => {
  const form = new VehicleMaintenancePage(page)
  await form.open()

  await page.waitForSelector(`#vehicle-maintenance-vehicle-ref option[value="${vehicle.ref}"]`)
  await page.waitForSelector(`#vehicle-maintenance-maintenance-type-ref option[value="${maintenanceType.ref}"]`)

  const fields = [
    ...fixture.fields,
    { selector: '#vehicle-maintenance-vehicle-ref',          type: 'select', value: vehicle.ref,         column: 'vehicle_ref' },
    { selector: '#vehicle-maintenance-maintenance-type-ref', type: 'select', value: maintenanceType.ref, column: 'maintenance_type_ref' },
  ]

  await form.fill(fields)
  const saved = await form.submit()
  for (const field of fields) {
    expect(String(saved[field.column] ?? ''), `response.${field.column}`).toBe(String(field.value))
  }
  await assertRecord(fixture.dbVerify, fields)
})
