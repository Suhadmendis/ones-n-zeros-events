import { BaseMasterFilePage } from '../../../../core/BaseMasterFilePage.js'
import fixture from './driver_master_file.fixture.js'

export class DriverMasterPage extends BaseMasterFilePage {
  constructor(page) { super(page, fixture) }

  async selectEmployee(ref) {
    await this.page.click('[data-bs-target="#driverEmployeePickerModal"]')
    await this.page.waitForSelector(`#driverEmployeePickerTable tr:has-text("${ref}")`)
    await this.page.click(`#driverEmployeePickerTable tr:has-text("${ref}")`)
  }
}
