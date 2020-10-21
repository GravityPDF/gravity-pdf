import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'
import { button } from './field'

class FontManager {
  constructor () {
    this.advancedButton = Selector('#gfpdf-settings-field-wrapper-font-container').find('button').withText('Manage')
    this.fontManagerPopup = Selector('div').find('[class^="container theme-wrap font-manager"]')

    // Header section
    this.popupHeaderText = Selector('h1').withText('Font Manager')
    this.closeDialog = Selector('.theme-header').find('button').withText('Close dialog')

    // Search bar section
    this.searchBar = Selector('#font-manager-search-box')

    // Font list section
    this.fontListColumn = Selector('.font-list-column')
    this.fontListItem = Selector('.font-list-item')
    this.fontName = Selector('.font-list-item').nth(0).find('[class^="font-name"]')
    this.fontVariantsCheck = Selector('.font-list-item').nth(0).find('[class^="dashicons dashicons-yes"]')

    // Add and update font panel section
    this.addFontColumn = Selector('.add-update-font-column')
    this.addFontNameInputField = Selector('#gfpdf-add-font-name-input')
    this.addNewFontRegular = Selector('#gfpdf-font-files-setting').find('input').withAttribute('aria-labelledby', 'gfpdf-font-variant-regular addFont gfpdf-font-files-label')
    this.addNewFontItalics = Selector('#gfpdf-font-files-setting').find('input').withAttribute('aria-labelledby', 'gfpdf-font-variant-italics updateFont gfpdf-font-files-label')
    this.addNewFontBold = Selector('#gfpdf-font-files-setting').find('input').withAttribute('aria-labelledby', 'gfpdf-font-variant-bold updateFont gfpdf-font-files-label')
    this.addNewFontBoldItalics = Selector('#gfpdf-font-files-setting').find('input').withAttribute('aria-labelledby', 'gfpdf-font-variant-bolditalics updateFont gfpdf-font-files-label')
    this.updateFontPanel = Selector('#gfpdf-font-manager-container').find('[class^="update-font show"]')
    this.updateFontNameInputField = Selector('#gfpdf-update-font-name-input')
    this.addFontButton = Selector('.footer').find('button').withText('Add Font →')
    this.updateFontButton = Selector('.footer').find('button').withText('Update Font →')
    this.cancelButton = Selector('.footer').find('[class^="button gfpdf-button primary cancel"]')

    // Success and error message
    this.fontListEmptyMessage = Selector('.alert-message').withText('Font list empty.')
    this.fontNameFieldErrorValidation = Selector('.input-label-validation-error')
    this.fontNameFieldErrorValidationMessage = Selector('.required').withText('Please choose a name contains letters and/or numbers (and a space if you want it).')
    this.dropzoneRequiredRegularField = Selector('#gfpdf-font-files-setting').find('[class^="drop-zone required"]')
    this.dropzoneErrorValidationMessage = Selector('.gfpdf-font-filename').withText('Add a .ttf font file.')
    this.addUpdatePanelGeneralErrorValidationMessage = Selector('.footer').withText(' Resolve the highlighted issues above and then try again.')
    this.successMessage = Selector('.success').withText('Your font has been saved.')

    // Resources
    this.gothamFontRegular = '../../utilities/resources/Gotham-Black-Regular.ttf'
    this.robotoFontRegular = '../../utilities/resources/Roboto-Regular.ttf'
    this.robotoFontItalics = '../../utilities/resources/Roboto-RegularItalic.ttf'
    this.robotoFontBold = '../../utilities/resources/Roboto-Bold.ttf'
    this.robotoFontBoldItalics = '../../utilities/resources/Roboto-BoldItalic.ttf'
  }

  async navigateFontManager (address) {
    await t
      .useRole(admin)
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${address}`)
      .click(button('Manage'))
  }
}

export default FontManager
