import { BaseMasterFilePage } from '../../../../core/BaseMasterFilePage.js'
import fixture from './customer_master_file.fixture.js'

export class CustomerMasterPage extends BaseMasterFilePage {
  constructor(page) { super(page, fixture) }
}
