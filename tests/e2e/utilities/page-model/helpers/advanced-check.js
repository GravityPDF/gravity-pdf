import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'
import { link, listItem } from '../helpers/field'
import Pdf from '../helpers/pdf'

const pdf = new Pdf()

class AdvancedCheck {
  constructor () {
    // Shortcode section
    this.confirmationTextCheckbox = Selector('#gform-settings-radio-choice-type0').find('input').withAttribute('id', 'type0')
    this.confirmationPageCheckbox = Selector('#gform-settings-radio-choice-type1').find('input').withAttribute('id', 'type1')
    this.confirmationRedirectCheckbox = Selector('#gform-settings-radio-choice-type2').find('input').withAttribute('id', 'type2')
    this.shortcodeInputBox = Selector('.gravitypdf_shortcode')
    this.confirmationPageSelectBox = Selector('#gform_setting_page').find('select').withAttribute('name', '_gform_setting_page')
    this.queryStringInputBox = Selector('#gform_setting_queryString').find('[id="queryString"]')
    this.confirmationRedirect = Selector('#form_confirmation_redirect')
    this.wysiwgEditorTextTab = Selector('.wp-editor-tabs').find('button').withText('Text')
    this.wysiwgEditor = Selector('div').find('[class^="merge-tag-support mt-wp_editor mt-manual_position mt-position-right wp-editor-area ui-autocomplete-input"]')
    this.redirectInputBox = Selector('#gform_setting_url').find('[id="url"]')
    this.previewLink = Selector('.gform-form-toolbar__container').find('a').withText('Preview')
    this.saveConfirmationButton = Selector('.gform-settings-save-container').find('button').withText('Save Confirmation')
    this.formInputField = Selector('input').withAttribute('name', 'input_1')
    this.submitButton = Selector('input').withAttribute('value', 'Submit')
    this.getStatusCode = null
    this.getContentDisposition = null
    this.getContentType = null

    // General Settings - Debug Mode field
    this.debugModeCheckbox = Selector('#gfpdf-fieldset-debug_mode').find('[class^="gform-field__toggle-container"]')
    this.debugModeErrorMessage = Selector('div').withText('PDF link not displayed because PDF is inactive.')

    // Merge tags section
    this.mergeTagsButton = Selector('#gform_setting_message').find('[class^="open-list tooltip-merge-tag"]')
    this.textInputField = Selector('input').withAttribute('name', 'input_1')
    this.fNameInputField = Selector('input').withAttribute('aria-label', 'First name')
    this.lNameInputField = Selector('input').withAttribute('aria-label', 'Last name')
    this.emailInputField = Selector('input').withAttribute('name', 'input_3')

    // PDF restriction section
    this.wpAdminBar = Selector('ul').withAttribute('id', 'wp-admin-bar-top-secondary').withAttribute('class', 'ab-top-secondary ab-top-menu')
    this.logout = Selector('a').withText('Log Out')
    this.pdfRestrictionErrorMessage = Selector('div').withAttribute('class', 'wp-die-message').withText('You do not have access to view this PDF.')
    this.entriesListFirstItem = Selector('tbody#the-list').find('tr').nth(0)
    this.viewEntryLink = Selector('.entry_unread').find('a').nth(5)
    this.wpLoginForm = Selector('#login').find('form').withAttribute('name', 'loginform')

    // Pdf template section
    this.addNewButton = Selector('.tablenav').find('a').withText('Add New')
    this.pdfLabelNameInputBox = Selector('#gfpdf-fieldset-gfpdf_form_settings_general').find('[id="gfpdf_settings[name]"]')
    this.pdfFilenameInputBox = Selector('#gfpdf-fieldset-gfpdf_form_settings_general').find('[id="gfpdf_settings[filename]"]')
    this.addUpdatePdfButton = Selector('#submit-and-promo-container').find('[id="submit"]')
    this.templateItem = Selector('#the-list').find('[class^="alternate"]')
    this.pdfListSection = Selector('.gform-settings__navigation').find('a').withText('PDF')
    this.toggleSwitch = Selector('.check-column').find('svg')
    this.activePdfTemplate = Selector('#the-list').find('[class^="gform-status-indicator gform-status--active"]')
    this.inactivePdfTemplate = Selector('#the-list').find('[class^="gform-status-indicator gform-status--inactive"]')
    this.entryItemSection = Selector('#the-list').find('a').withAttribute('aria-label', 'View this entry')
    this.viewPdfLink = Selector('#the-list').find('a').withText('View PDF')
    this.editLink = Selector('#the-list').find('span').withText('Edit')
    this.conditionalLogicCheckbox = Selector('#gfpdf-fieldset-gfpdf_form_settings_general').find('[id="gfpdf_conditional_logic"]')
    this.templateDetail = Selector('.alternate').find('td').nth(0)
    this.duplicateLink = Selector('#the-list').find('a').withText('Duplicate')
    this.deletePDF = Selector('.submitdelete')
  }

  async copyDownloadShortcode (text) {
    await t
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(this.shortcodeInputBox)
  }

  async navigateConfirmationSection (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(link('#the-list', 'Default Confirmation'))
  }

  async responseStatus (data, id) {
    let objectHolder

    objectHolder = data
    const requestId = Object.keys(objectHolder)[id]
    objectHolder = data[requestId].response.headers

    this.getStatusCode = data[requestId].response.statusCode
    this.getContentDisposition = objectHolder['content-disposition']
    this.getContentType = objectHolder['content-type']
  }

  async navigateLink (text) {
    await t
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }

  async navigateSection (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
  }

  async pickMergeTag (text) {
    await t
      .click(this.mergeTagsButton)
      .click(listItem(text))
      .pressKey('enter')
  }

  async pdfRestrictionLogin (role) {
    await t
      .typeText('#user_login', role, { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
  }

  async WpLogout () {
    await t
      .hover(this.wpAdminBar)
      .click(this.logout)
  }

  async toggleRestrictOwnerCheckbox (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
      .wait(500)
      .click(Selector('td.name').find('a').withText('Sample'))
      .click(pdf.advancedCollapsiblePanel)
      .click(pdf.restrictOwnerCheckbox)
      .click(pdf.saveSettings)
  }

  async submitNewPdfEntry () {
    await t
      .typeText(this.textInputField, 'texttest', { paste: true })
      .click(this.submitButton)
      .wait(500)
  }

  async navigateAddPdf (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
      .wait(500)
      .click(this.addNewButton)
      .typeText(this.pdfLabelNameInputBox, 'Test PDF Template', { paste: true })
      .typeText(this.pdfFilenameInputBox, 'testpdftemplate', { paste: true })
      .click(this.addUpdatePdfButton)
      .wait(500)
      .click(this.pdfListSection)
  }

  async navigateDeletePdfEntries (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
    const template = await this.templateItem.count
    if (template > 0) {
      for (let i = 0; i < template; i++) {
        await t
          .hover(this.templateItem)
          .click(this.deletePDF)
          .wait(2000)
      }
    }
  }
}

export default AdvancedCheck
