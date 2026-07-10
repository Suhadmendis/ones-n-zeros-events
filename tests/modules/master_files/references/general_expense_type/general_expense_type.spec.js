import { test, expect } from '@playwright/test'
import { GeneralExpenseTypePage } from './general_expense_type.page.js'
import { assertRecord, deleteRecord } from '../../../../helpers/db.js'
import fixture from './general_expense_type.fixture.js'

test.beforeEach(async () => { await deleteRecord(fixture.dbVerify) })
test.afterEach(async ()  => { await deleteRecord(fixture.dbVerify) })

test('general expense type: fill, save, and persist', async ({ page }) => {
  const form = new GeneralExpenseTypePage(page)
  await form.open()
  await form.fill(fixture.fields)
  const saved = await form.submit()
  for (const field of fixture.fields) {
    expect(String(saved[field.column] ?? ''), `response.${field.column}`).toBe(String(field.value))
  }
  await assertRecord(fixture.dbVerify, fixture.fields)
})
