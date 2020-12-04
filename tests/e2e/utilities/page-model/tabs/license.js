import { Selector, t } from 'testcafe'
import { baseURL } from '../../../auth'

class License {
  constructor () {
    // Core Booster field
    this.coreBoosterInputBox = Selector('#gfpdf-fieldset-license_gravity-pdf-core-booster').find('[id="gfpdf_settings[license_gravity-pdf-core-booster]"]')
    this.validLicenseKey = '987654321'
    this.invalidLicenseKey = '123456789'
    this.invalidLicenseKeyMessage = Selector('.error').withText('Invalid license key provided')
    this.deactivateLinkMessage = Selector('button').withText('Deactivate License')
    this.successMessage = Selector('.success').withText('Your support license key has been successfully validated.')
    this.deactivateLink = Selector('.gfpdf-deactivate-license')
    this.deactivatedMessage = Selector('.success').withText('License deactivated.')

    this.saveSettings = Selector('#submit-and-promo-container').find('input')
  }

  async navigateSettingsTab (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
  }

  async navigateCoreBooster (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
      .click(this.coreBoosterInputBox)
      .selectText(this.coreBoosterInputBox, 32, 0)
      .pressKey('backspace')
  }
}

export default License
