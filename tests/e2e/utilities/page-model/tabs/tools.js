import { Selector, t } from 'testcafe'
import { baseURL } from '../../../auth'

class Tools {
  constructor () {
    // Fieldset collapsible link
    this.uninstallGravityPdfCollapsiblePanel = Selector('#gfpdf-fieldset-uninstaller').find('[id="gform_settings_section_collapsed_uninstaller"]')

    // Install Core Fonts field
    this.downloadCoreFontsButton = Selector('#gfpdf-fieldset-install_core_fonts').find('button').withText('Download Core Fonts')
    this.pendingResult = Selector('.gfpdf-core-font-status-pending')
    this.downloadSuccess = Selector('.gfpdf-core-font-status-success')
    this.allSuccessfullyIntalled = Selector('.gfpdf-core-font-status-success').withText('ALL CORE FONTS SUCCESSFULLY INSTALLED')
    this.downloadFailed = Selector('.gfpdf-core-font-status-error')
    this.retryDownload = Selector('a').withText('Retry Failed Downloads?')

    // Uninstall Gravity PDF field
    this.uninstallGravityPdfButton = Selector('#gfpdf-fieldset-uninstaller').find('button').withText('Uninstall Gravity PDF')
  }

  async navigateSettingsTab (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
  }
}

export default Tools
