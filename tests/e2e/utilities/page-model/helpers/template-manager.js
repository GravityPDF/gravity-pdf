import { Selector, t } from 'testcafe'
import { baseURL } from '../../../auth'
import { button } from './field'

class TemplateManager {
  constructor () {
    this.advancedButton = Selector('#gpdf-advance-template-selector').find('button').withText('Manage')
    this.templatePopupBox = Selector('div').find('[class^="container theme-wrap"]')
    this.closeDialog = button('Close dialog')
    this.popupHeaderText = Selector('h1').withText('Installed PDFs')
    this.searchBar = Selector('#wp-filter-search-input')
    this.individualThemeBox = Selector('.theme')
    this.themeScreenshot = Selector('.theme-screenshot')
    this.themeAuthor = Selector('.theme-author')
    this.themeName = Selector('.theme-name')
    this.themeSelectButton = Selector('a').withText('Select')
    this.themeDetailsLink = Selector('span').withText('Template Details')
    this.dropZoneBox = Selector('.dropzone')
    this.installationMessage = Selector('div')
    this.focusGravityTemplateDetails = Selector('.theme-wrap').find('div').withAttribute('data-slug', 'focus-gravity').withText('Template Details')
    this.focusGravityTemplate = Selector('div').find('[class^="theme-name"]').withText('Focus Gravity')
    this.rubixTemplate = Selector('div').find('[class^="theme-name"]').withText('Rubix')
    this.templateSearchbar = Selector('#wp-filter-search-input')
    this.searchResult = Selector('.theme-author')
    this.addNewTemplateButton = Selector('input').withAttribute('type', 'file')
    this.testTemplate = '../../utilities/resources/test-template.zip'
    this.imageScreenshot = Selector('.theme-screenshot').find('img').withAttribute('src', `${baseURL}/wp-content/uploads/PDF_EXTENDED_TEMPLATES/images/test-template.png`)
    this.deleteButton = Selector('.button').withText('Delete')
    this.testTemplateDetailsLink = Selector('.theme[data-slug="test-template"]').find('span').withText('Template Details')
  }

  async navigateTemplateManager (address) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${address}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
      .click(Selector('#the-list').find('a').nth(0).withText('Sample'))
      .click(this.advancedButton)
  }
}

export default TemplateManager
