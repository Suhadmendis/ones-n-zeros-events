import { test, expect } from '@playwright/test'
import { VehicleGpsPage } from './vehicle_gps.page.js'
import { assertRecord, deleteRecord, seedRecord, getAnyRecord } from '../../../../helpers/db.js'
import fixture from './vehicle_gps.fixture.js'

const uid = () => Math.random().toString(36).slice(2, 8).toUpperCase()

let vehicle, gpsDevice

test.beforeEach(async () => {
  await deleteRecord(fixture.dbVerify)
  vehicle   = await getAnyRecord('m_vehicles')
  gpsDevice = await seedRecord('m_gps_devices', { ref: `TGPS${uid()}`, code: `TG${uid()}`, status: 'active' })
})

test.afterEach(async () => {
  await deleteRecord(fixture.dbVerify)
  await deleteRecord({ table: 'm_gps_devices', column: 'ref', value: gpsDevice.ref })
})

test('vehicle gps: fill, save, and persist', async ({ page }) => {
  const form = new VehicleGpsPage(page)
  await form.open()

  await page.waitForSelector(`#vehicle-gps-vehicle-ref option[value="${vehicle.ref}"]`)
  await page.waitForSelector(`#vehicle-gps-gps-device-ref option[value="${gpsDevice.ref}"]`)

  const fields = [
    ...fixture.fields,
    { selector: '#vehicle-gps-vehicle-ref',     type: 'select', value: vehicle.ref,   column: 'vehicle_ref' },
    { selector: '#vehicle-gps-gps-device-ref',  type: 'select', value: gpsDevice.ref, column: 'gps_device_ref' },
  ]

  await form.fill(fields)
  const saved = await form.submit()
  for (const field of fields) {
    expect(String(saved[field.column] ?? ''), `response.${field.column}`).toBe(String(field.value))
  }
  await assertRecord(fixture.dbVerify, fields)
})
