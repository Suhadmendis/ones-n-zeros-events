import { BaseFormPage } from '../../../../core/BaseFormPage.js'
import fixture from './company_profile.fixture.js'

export class CompanyProfilePage extends BaseFormPage {
  constructor(page) {
    super(page)
    this.fixture = fixture
  }

  async open() {
    const [response] = await Promise.all([
      this.page.waitForResponse(r => r.url().includes('action=get') && r.status() === 200),
      super.open(this.fixture.url),
    ])
    return response.json()
  }

  async submit() {
    const buttonSelector = '.btn-success.btn-lg'
    await this.page.waitForSelector(`${buttonSelector}:not([disabled])`)
    const [response] = await Promise.all([
      this.page.waitForResponse(r => r.url().includes('action=update') && r.status() === 200),
      this.page.click(buttonSelector),
    ])
    return response.json()
  }
}
