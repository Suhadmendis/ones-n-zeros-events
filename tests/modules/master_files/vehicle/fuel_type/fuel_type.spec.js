import { test, expect } from '@playwright/test'
import { FuelTypePage } from './fuel_type.page.js'
import { assertRecord, deleteRecord } from '../../../../helpers/db.js'
import fixture from './fuel_type.fixture.js'

test.beforeEach(async () => { await deleteRecord(fixture.dbVerify) })
test.afterEach(async ()  => { await deleteRecord(fixture.dbVerify) })

test('fuel type: fill, save, and persist', async ({ page }) => {
  const form = new FuelTypePage(page)
  await form.open()
  await form.fill(fixture.fields)
  const saved = await form.submit()
  for (const field of fixture.fields) {
    expect(String(saved[field.column] ?? ''), `response.${field.column}`).toBe(String(field.value))
  }
  await assertRecord(fixture.dbVerify, fixture.fields)
})
