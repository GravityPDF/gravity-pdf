import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../../auth'
import { button } from '../../../helpers/field'

class Fonts {
  constructor () {
    this.manageFontsPopupBox = Selector('div').withAttribute('aria-describedby', 'manage-font-files')
    this.addFontIcon = Selector('.dashicons-plus')
    this.deleteIcon = Selector('.dashicons-trash')
    this.confirmDeletePopupBox = Selector('div').withAttribute('aria-describedby', 'delete-confirm')
    this.fontList = Selector('#font-list')
    this.cancelButton = Selector('div').withAttribute('aria-describedby', 'delete-confirm').find('button').withText('Cancel')
    this.fontListEmpty = Selector('#font-empty').withText('Looks bare in here!\n' + 'Click "Add Font" below to get started.')
  }

  async navigateSettingsTab (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(button('Manage Fonts'))
  }
}

export default Fonts
