import { BaseMasterFilePage } from '../../../../core/BaseMasterFilePage.js'
import fixture from './cleaner_master_file.fixture.js'

export class CleanerMasterPage extends BaseMasterFilePage {
  constructor(page) { super(page, fixture) }

  async selectEmployee(ref) {
    await this.page.click('[data-bs-target="#cleanerEmployeePickerModal"]')
    await this.page.waitForSelector(`#cleanerEmployeePickerTable tr:has-text("${ref}")`)
    await this.page.click(`#cleanerEmployeePickerTable tr:has-text("${ref}")`)
  }
}
