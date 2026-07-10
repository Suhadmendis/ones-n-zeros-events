import { defineConfig } from '@playwright/test'
import path from 'path'
import { fileURLToPath } from 'url'

process.loadEnvFile()

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const authFile = path.join(__dirname, 'tests/.auth/session.json')

export default defineConfig({
  use: {
    baseURL: 'http://localhost:8000',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'setup',
      testDir: './tests/setup',
      testMatch: 'auth.setup.js',
    },
    {
      name: 'chromium',
      testDir: './tests/modules',
      dependencies: ['setup'],
      use: {
        storageState: authFile,
      },
    },
  ],
})
