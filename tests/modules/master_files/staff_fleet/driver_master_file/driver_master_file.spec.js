import { test, expect } from '@playwright/test'
import { DriverMasterPage } from './driver_master_file.page.js'
import { assertRecord, deleteRecord, seedRecord } from '../../../../helpers/db.js'
import fixture from './driver_master_file.fixture.js'

const uid = () => Math.random().toString(36).slice(2, 8).toUpperCase()

let employee

test.beforeEach(async () => {
  await deleteRecord(fixture.dbVerify)
  employee = await seedRecord('m_employees', { ref: `TEMP${uid()}`, code: `TE${uid()}`, full_name: 'TEST Employee Playwright', status: 'active' })
})

test.afterEach(async () => {
  await deleteRecord(fixture.dbVerify)
  await deleteRecord({ table: 'm_employees', column: 'ref', value: employee.ref })
})

test('driver master file: fill, save, and persist', async ({ page }) => {
  const form = new DriverMasterPage(page)
  await form.open()
  await form.fill(fixture.fields)
  await form.selectEmployee(employee.ref)

  const fields = [
    ...fixture.fields,
    { selector: '#dr-employee-ref', value: employee.ref, column: 'employee_ref' },
  ]

  const saved = await form.submit()
  for (const field of fields) {
    expect(String(saved[field.column] ?? ''), `response.${field.column}`).toBe(String(field.value))
  }
  await assertRecord(fixture.dbVerify, fields)
})
