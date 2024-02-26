import { Selector, t } from 'testcafe'
import Pdf from '../helpers/pdf'

const pdf = new Pdf()

class License {
  constructor () {
    // Core Booster field
    this.samplePluginInputBox = Selector('#gfpdf-fieldset-license_gravity-pdf-example-plugin').find('[id="gfpdf_settings[license_gravity-pdf-example-plugin]"]')
    this.validLicenseKey = '987654321'
    this.invalidLicenseKey = '123456789'
    this.invalidLicenseKeyMessage = Selector('.gforms_note_error').withText('This license key is invalid. Please check your key has been entered correctly.')
    this.deactivateLinkMessage = Selector('button').withText('Deactivate License')
    this.successMessage = Selector('.gforms_note_success').withText('Your support license key has been activated for this domain.')
    this.deactivateLink = Selector('.gfpdf-deactivate-license')
    this.deactivatedMessage = Selector('.success').withText('License deactivated.')

    this.saveSettings = Selector('#submit-and-promo-container').find('input')
  }

  async navigateCoreBooster (uri) {
    await pdf.navigate(uri)
    await t
      .click(this.samplePluginInputBox)
      .selectText(this.samplePluginInputBox, 32, 0)
      .pressKey('backspace')
  }
}

export default License
