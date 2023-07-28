import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'

class Tools {
  constructor () {
    // Install Core Fonts field
    this.downloadCoreFontsButton = Selector('#gfpdf-fieldset-install_core_fonts').find('button').withText('Download Core Fonts')
    this.pendingResult = Selector('.gfpdf-core-font-status-pending')
    this.downloadSuccess = Selector('.gfpdf-core-font-status-success')
    this.allSuccessfullyInstalled = Selector('.gfpdf-core-font-status-success').withText('ALL CORE FONTS SUCCESSFULLY INSTALLED')
    this.downloadFailed = Selector('.gfpdf-core-font-status-error')
    this.retryDownload = Selector('a').withText('Retry Failed Downloads?')

    // Uninstall Gravity PDF field
    this.uninstallPanelTitle = Selector('.addon-uninstall-text').find('h4').withText('Gravity PDF')
    this.uninstallPanelDescription = Selector('.addon-uninstall-text').find('div').withText('This operation deletes ALL Gravity PDF settings.')
    this.uninstallGravityPdfButton = Selector('.addon-uninstall-button').find('button').withText('Uninstall')
  }

  async navigateSettingsTab (address) {
    await t
      .useRole(admin)
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${address}`)
  }
}

export default Tools
