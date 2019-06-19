import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../../auth'
import { button } from '../../../helpers/field'

class Fonts {
  constructor () {
    this.manageFontsPopupBox = Selector('div').withAttribute('aria-describedby', 'manage-font-files')
    this.addFontIcon = Selector('.fa-plus')
    this.deleteIcon = Selector('.fa-trash-o')
    this.confirmDeletePopupBox = Selector('div').withAttribute('aria-describedby', 'delete-confirm')
    this.fontList = Selector('#font-list')
    this.cancelButton = Selector('div').withAttribute('aria-describedby', 'delete-confirm').find('[class^="ui-button ui-widget ui-state-default ui-corner-all"]').find('span').withText('Cancel')
    this.fontListEmpty = Selector('#font-empty').withText('Looks bare in here!\n' + 'Click "Add Font" below to get started.')
  }

  async navigateSettingsTab (text) {
    await t
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(button('Manage Fonts'))
  }
}

export default Fonts
