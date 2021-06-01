import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../auth'
import { link } from '../helpers/field'

let objectHolder
let requestId

class ConfirmationShortcodes {
  constructor () {
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
    objectHolder = data
    requestId = Object.keys(objectHolder)[id]
    objectHolder = data[requestId].response.headers

    this.getStatusCode = data[requestId].response.statusCode
    this.getContentDisposition = objectHolder['content-disposition']
    this.getContentType = objectHolder['content-type']
  }
}

export default ConfirmationShortcodes
