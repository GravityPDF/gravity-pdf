import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'

class General {
  constructor () {
    this.advancedOptionsField = Selector('#gfpdf-advanced-options')
    this.templatePopupBox = Selector('div').find('[class^="container theme-wrap"]')
    this.viewOption = Selector('div').find('[class^="gfpdf_settings_default_action"][value="View"]')
    this.downlaodOption = Selector('div').find('[class^="gfpdf_settings_default_action"][value="Download"]')
    this.entries = Selector('#the-list').find('tr').withText('Sample 2').find('span').withText('Entries')
    this.list = Selector('.gf-locking ').withText('Sample 2')
    this.template = Selector('.alternate')
    this.templateList = Selector('#the-list')
    this.editLink = Selector('span').withText('Edit')
    this.generalLink = Selector('#gfpdf-general-options')
    this.appearanceLink = Selector('#gfpdf-appearance-nav')
    this.focusGravity = Selector('.theme[data-slug="focus-gravity"]').find('span').withText('Template Details')
    this.zadaniDetailsLink = Selector('.theme[data-slug="zadani"]').find('span').withText('Template Details')
    this.testTemplateDetailsLink = Selector('.theme[data-slug="test-template"]').find('span').withText('Template Details')
    this.templateSelectButton = Selector('a').withText('Select')
    this.paperSizeField = Selector('#gfpdf_settings_default_pdf_size__chosen')
    this.fontField = Selector('#gfpdf_settings_default_font__chosen')
    this.fontSize = Selector('#gfpdf_settings\\[default_font_size\\]')
    this.successUpdateMessage = Selector('p').withText('Settings updated.')
    this.entryItem = Selector('a').withAttribute('aria-label', 'View this entry')
    this.addNewTemplate = Selector('input').withAttribute('type', 'file')
    this.saveButton = Selector('div').find('[class^="button button-primary"][value="Save Changes"]')
  }

  async navigateSettingsTab (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }

  async navigatePdfEntries (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }
}

export default General
