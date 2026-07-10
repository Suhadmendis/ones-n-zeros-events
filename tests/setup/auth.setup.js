import { test as setup } from '@playwright/test'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
export const authFile = path.join(__dirname, '../.auth/session.json')

setup('authenticate', async ({ page }) => {
  await page.goto('/login.php')
  await page.fill('#login-email', process.env.TEST_EMAIL)
  await page.fill('#login-password', process.env.TEST_PASSWORD)
  await page.click('.btn-login')
  await page.waitForURL('**/index.php')
  await page.context().storageState({ path: authFile })
})
