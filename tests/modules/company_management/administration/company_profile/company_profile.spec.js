import { test, expect } from '@playwright/test'
import { CompanyProfilePage } from './company_profile.page.js'
import { assertSettings } from '../../../../helpers/db.js'
import fixture from './company_profile.fixture.js'

// Company profile is a singleton settings record (not a disposable test row),
// so each test must restore the real original values afterward instead of deleting anything.
let original

test.beforeEach(async ({ page }) => {
  const form = new CompanyProfilePage(page)
  original = await form.open()
})

test.afterEach(async ({ page }) => {
  await page.request.post(`${fixture.apiUrl}?action=update`, { form: original })
})

test('company profile: fill, save, and persist', async ({ page }) => {
  const form = new CompanyProfilePage(page)
  await form.fill(fixture.fields)
  const saved = await form.submit()

  for (const field of fixture.fields) {
    expect(String(saved[field.field] ?? ''), `response.${field.field}`).toBe(String(field.value))
  }
  await assertSettings(fixture.fields)
})
