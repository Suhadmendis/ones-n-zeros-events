import { BaseMasterFilePage } from '../../../../core/BaseMasterFilePage.js'
import fixture from './vehicle_master_file.fixture.js'

export class VehicleMasterPage extends BaseMasterFilePage {
  constructor(page) { super(page, fixture) }
}
