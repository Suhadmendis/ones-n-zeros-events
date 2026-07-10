import { BaseFormPage } from './BaseFormPage.js'

export class BaseMasterFilePage extends BaseFormPage {
  constructor(page, fixture) {
    super(page)
    this.fixture = fixture
  }

  async open() {
    await super.open(this.fixture.url)
    const { refSelector, refPrefix } = this.fixture
    await this.page.waitForFunction(
      ({ sel, prefix }) => {
        const el = document.querySelector(sel)
        return el && el.value && el.value.startsWith(prefix)
      },
      { sel: refSelector, prefix: refPrefix }
    )
  }

  async submit() {
    return super.submit('.btn-success.btn-lg')
  }
}
