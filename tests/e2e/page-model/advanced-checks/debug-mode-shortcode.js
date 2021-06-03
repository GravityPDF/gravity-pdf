import { Selector, t } from 'testcafe'
import { radioItem } from '../helpers/field'
import { baseURL } from '../../auth'

class DebugModeShortcode {
  constructor () {
    this.saveButton = Selector('input').withAttribute('value', 'Save Changes')
    this.errorMessage = Selector('div').withText('PDF link not displayed because PDF is inactive.')
    this.activePDF = Selector('img').withAttribute('alt', 'Active').withAttribute('title', 'Active')
    this.noSelected = radioItem('gfpdf_settings', 'debug_mode', 'No').withAttribute('checked', 'checked')
  }

  async navigateLink (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }
}

export default DebugModeShortcode
