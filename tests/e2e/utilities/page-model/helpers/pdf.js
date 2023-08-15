import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'
import { selectBox } from './field'

class Pdf {
  constructor () {
    this.template = Selector('.alternate')
    this.saveSettings = Selector('#submit-and-promo-container').find('input')

    // General - Template field
    this.templateSelectBox = selectBox('gfpdf_settings_template large', 'gfpdf_settings[template]')

    // General - Filename field
    this.filenameInputBox = Selector('#gfpdf-settings-field-wrapper-filename').find('[id="gfpdf_settings[filename]"]')
    this.filenameMergeTagsOptionList = Selector('#gfpdf-settings-field-wrapper-filename').find('button.gform-dropdown__control')

    // General - Conditional Logic field
    this.conditionalLogicCheckbox = Selector('#gfpdf-settings-field-wrapper-conditional').find('[id="gfpdf_conditional_logic"]')

    // Appearance - Paper Size field
    this.paperSizeSelectBox = Selector('#gfpdf-settings-field-wrapper-pdf_size').find('[id="gfpdf_settings[pdf_size]"]')

    // Appearance - Paper Orientation field
    this.paperOrientationSelectBox = selectBox('gfpdf_settings_orientation large', 'gfpdf_settings[orientation]')

    // Appearance - Custom Paper Size field
    this.customPaperSizeLabel = Selector('#gfpdf-settings-field-wrapper-custom_pdf_size').find('[class^="gform-settings-panel__title"]').withText('Custom Paper Size')

    // Appearance - Font field
    this.fontSelectBox = selectBox('gfpdf_settings_font ', 'gfpdf_settings[font]')

    // Appearance - Font Size field
    this.fontSizeInputBox = selectBox('small-text gfpdf_settings_font_size ', 'gfpdf_settings[font_size]')

    // Appearance - Font Color field
    this.fontColorSelectButton = Selector('#gfpdf-settings-field-wrapper-font_colour').find('button').withText('Select Color')
    this.fontColorWpPickerContainerActive = Selector('#gfpdf-settings-field-wrapper-font_colour').find('[class^="wp-picker-container wp-picker-active"]')
    this.fontColorWpColorPickerBox = Selector('#gfpdf-settings-field-wrapper-font_colour').find('[class^="iris-picker iris-border"]')
    this.fontColorInputBox = Selector('#gfpdf-settings-field-wrapper-font_colour').find('[id="gfpdf_settings[font_colour]"]')

    // Appearance - Reverse Text (RTL) field
    this.rtlCheckbox = Selector('#gfpdf-settings-field-wrapper-rtl').find('[id="gfpdf_settings[rtl]"]')

    // Template - Show Form Title field
    this.showFormTitleCheckbox = Selector('#gfpdf-settings-field-wrapper-show_form_title').find('[id="gfpdf_settings[show_form_title]"]')

    // Template - Show Page Names field
    this.showPageNamesCheckbox = Selector('#gfpdf-settings-field-wrapper-show_page_names').find('[id="gfpdf_settings[show_page_names]"]')

    // Template - Show HTML Fields field
    this.showHtmlFieldsCheckbox = Selector('#gfpdf-settings-field-wrapper-show_html').find('[id="gfpdf_settings[show_html]"]')

    // Template - Show Section Break Description field
    this.showSectionBreakDescriptionCheckbox = Selector('#gfpdf-settings-field-wrapper-show_section_content').find('[id="gfpdf_settings[show_section_content]"]')

    // Template - Enable Conditional Logic field
    this.enableConditionalLogicCheckbox = Selector('#gfpdf-settings-field-wrapper-enable_conditional').find('[id="gfpdf_settings[enable_conditional]"]')

    // Template - Show Empty Fields field
    this.showEmptyFieldsCheckbox = Selector('#gfpdf-settings-field-wrapper-show_empty').find('[id="gfpdf_settings[show_empty]"]')

    // Template - Background Color field
    this.backgroundColorInputBox = Selector('#gfpdf-settings-field-wrapper-background_color').find('[id="gfpdf_settings[background_color]"]')
    this.backgroundColorWpPickerContainerActive = Selector('#gfpdf-settings-field-wrapper-background_color').find('[class^="wp-picker-container wp-picker-active"]')
    this.backgroundColorWpColorPickerBox = Selector('#gfpdf-settings-field-wrapper-font_colour').find('[class^="iris-picker iris-border"]')
    this.backgroundColorSelectButton = Selector('#gfpdf-settings-field-wrapper-background_color').find('button').withText('Select Color')

    // Template - Background Image field
    this.backgroundImageUploadBox = selectBox('regular-text gfpdf_settings_background_image ', 'gfpdf_settings[background_image]')
    this.backgroundImageUploadFileButton = Selector('.gfpdf-upload-setting-container').find('input').withAttribute('type', 'button')

    // Template - Header field
    this.headerWpEditorBox = Selector('#gfpdf-settings-field-wrapper-header').find('[id="wp-gfpdf_settings_header-editor-container"]')
    this.headerWpEditorBoxTextPanelLink = Selector('#gfpdf-settings-field-wrapper-header').find('.wp-editor-tabs').find('button').withText('Text')
    this.headerWpEditorBoxContentArea = Selector('#gfpdf-settings-field-wrapper-header').find('[class^="gfpdf_settings_header"]')

    // Template - First Page Header field
    this.firstPageHeaderCheckbox = Selector('#gfpdf-settings-field-wrapper-first_header').find('[class^="gfpdf-input-toggle"]')
    this.firstPageHeaderWpEditorBox = Selector('#gfpdf-settings-field-wrapper-first_header').find('[id="wp-gfpdf_settings_first_header-editor-container"]')
    this.firstPageHeaderWpEditorBoxTextPanelLink = Selector('#gfpdf-settings-field-wrapper-first_header').find('.wp-editor-tabs').find('button').withText('Text')
    this.firstPageHeaderWpEditorBoxContentArea = Selector('#gfpdf-settings-field-wrapper-first_header').find('[class^="gfpdf_settings_first_header"]')

    // Template - Footer field
    this.footerWpEditorBox = Selector('#gfpdf-settings-field-wrapper-footer').find('[id="wp-gfpdf_settings_footer-editor-container"]')
    this.footerWpEditorBoxTextPanelLink = Selector('#gfpdf-settings-field-wrapper-footer').find('.wp-editor-tabs').find('button').withText('Text')
    this.footerWpEditorBoxContentArea = Selector('#gfpdf-settings-field-wrapper-footer').find('[class^="gfpdf_settings_footer"]')

    // Template - First Page Footer field
    this.firstPageFooterCheckbox = Selector('#gfpdf-settings-field-wrapper-first_footer').find('[class^="gfpdf-input-toggle"]')
    this.firstPageFooterWpEditorBox = Selector('#gfpdf-settings-field-wrapper-first_footer').find('[id="wp-gfpdf_settings_first_footer-editor-container"]')
    this.firstPageFooterWpEditorBoxTextPanelLink = Selector('#gfpdf-settings-field-wrapper-first_footer').find('.wp-editor-tabs').find('button').withText('Text')
    this.firstPageFooterWpEditorBoxContentArea = Selector('#gfpdf-settings-field-wrapper-first_footer').find('[class^="gfpdf_settings_first_footer"]')

    // Advanced - Format field
    this.formatStandardCheckbox = Selector('#gfpdf-settings-field-wrapper-format').find('[id="gfpdf_settings[format][Standard]"]')
    this.formatPdfA1bCheckbox = Selector('#gfpdf-settings-field-wrapper-format').find('[id="gfpdf_settings[format][PDFA1B]"]')
    this.formatPdfX1aCheckbox = Selector('#gfpdf-settings-field-wrapper-format').find('[id="gfpdf_settings[format][PDFX1A]"]')
    this.enablePdfSecurityField = Selector('#gfpdf-settings-field-wrapper-security')
    this.enablePdfSecurityCheckbox = Selector('#gfpdf-settings-field-wrapper-security').find('[id="gfpdf_settings[security]"]')
    this.passwordField = Selector('#gfpdf-settings-field-wrapper-password')
    this.privilegesField = Selector('#gfpdf-settings-field-wrapper-privileges')

    // Advanced - Enable PDF Security field
    this.enablePdfSecurityCheckbox = Selector('#gfpdf-settings-field-wrapper-security').find('[id="gfpdf_settings[security]"]')

    // Advanced - Password field
    this.passwordInputBox = Selector('#gfpdf-settings-field-wrapper-password').find('[id="gfpdf_settings[password]"]')
    this.passwordMergeTagsOptionList = Selector('#gfpdf-settings-field-wrapper-password').find('button.gform-dropdown__control')

    // Advanced - Privileges field
    this.copyCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][copy]"]')
    this.printLowResolutionCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][print]"]')
    this.printHighResolutionCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][print-highres]"]')
    this.modifyCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][modify]"]')
    this.annotateCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][annot-forms]"]')
    this.fillFormsCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][fill-forms]"]')
    this.extractCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][extract]"]')
    this.assembleCheckbox = Selector('#gfpdf-settings-field-wrapper-privileges').find('[id="gfpdf_settings[privileges][assemble]"]')

    // Advanced - Image DPI field
    this.imageDpiInputBox = Selector('#gfpdf-settings-field-wrapper-image_dpi').find('[id="gfpdf_settings[image_dpi]"]')

    // Advanced - Enable Public Access field
    this.enablePublicAccessCheckbox = Selector('#gfpdf-settings-field-wrapper-public_access').find('[id="gfpdf_settings[public_access]"]')

    // Advanced - Restrict Owner field
    this.restrictOwnerCheckbox = Selector('#gfpdf-settings-field-wrapper-restrict_owner').find('[id="gfpdf_settings[restrict_owner]"]')
  }

  async navigatePdfSection (uri) {
    await this.navigate(uri)
    await t.click(Selector('#the-list')
      .find('a')
      .nth(0)
      .withText('Sample'))
  }

  async navigate (uri) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${uri}`)
  }
}

export default Pdf
