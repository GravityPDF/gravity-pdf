import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'

class License {
  constructor () {
    this.licenseInputField = Selector('#gfpdf_settings\\[license_gravity-pdf-core-booster\\]')
    this.saveButton = Selector('input').withAttribute('value', 'Save Changes')
    this.validLicenseKey = '987654321'
    this.invalidLicenseKey = '123456789'
    this.errorIcon = Selector('.fa-exclamation-circle')
    this.invalidLicenseKeyMessage = Selector('label').withText('An error occurred during activation, please try again')
    this.successIcon = Selector('.fa-check')
    this.deactivateLinkMessage = Selector('a').withText('Deactivate License')
    this.deactivateLink = Selector('.gfpdf-deactivate-license')
    this.deactivatedMessage = Selector('label').withText('License deactivated.')
  }

  async navigateSettingsTab (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(this.licenseInputField)
      .selectText(this.licenseInputField, 32, 0)
      .pressKey('backspace')
  }
}

export default License
