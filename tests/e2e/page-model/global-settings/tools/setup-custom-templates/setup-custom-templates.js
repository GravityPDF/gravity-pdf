import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../../auth'
import { button } from '../../../helpers/field'

class SetupCustomTemplates {
  constructor () {
    this.runSetupButton = button('Run Setup')
    this.popUpBox = Selector('div').withAttribute('aria-describedby', 'setup-templates-confirm')
    this.continueButton = button('Continue')
    this.cancelButton = button('Cancel')
    this.successUpdateMessage = Selector('div').find('[class^="updated  notice"]').find('p').withText('Gravity PDF Custom Templates successfully installed to')
  }

  async navigateSettingsTab (text) {
    await t
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(this.runSetupButton)
  }
}

export default SetupCustomTemplates
