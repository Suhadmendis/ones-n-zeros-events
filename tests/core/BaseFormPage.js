import { BasePage } from './BasePage.js'

export class BaseFormPage extends BasePage {
  async fill(fields) {
    for (const field of fields) {
      if (field.type === 'select') {
        await this.page.selectOption(field.selector, field.value)
      } else if (field.type === 'checkbox') {
        await this.page.setChecked(field.selector, field.value)
      } else {
        await this.page.fill(field.selector, field.value)
      }
    }
  }

  async submit(buttonSelector = '.btn-success') {
    await this.page.waitForSelector(`${buttonSelector}:not([disabled])`)
    const [response] = await Promise.all([
      this.page.waitForResponse(r => r.url().includes('action=save') && r.status() === 200),
      this.page.click(buttonSelector),
    ])
    return response.json()
  }

  async assertSuccess(text) {
    await this.page.waitForSelector(`span.text-success:has-text("${text}")`)
  }

  async assertError(text) {
    await this.page.waitForSelector(`span.text-danger:has-text("${text}")`)
  }
}
