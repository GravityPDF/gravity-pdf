import { Selector, t } from 'testcafe'
import { baseURL } from '../../../auth'

class General {
  constructor () {
    // Fieldset collapsible link
    this.securityCollapsiblePanel = Selector('#gfpdf-fieldset-gfpdf_settings_general_security').find('[id="gform_settings_section_collapsed_gfpdf_settings_general_security"]')

    // General Settings - Default Template field
    this.defaultTemplateSelectBox = Selector('#gfpdf-settings-field-wrapper-default_template').find('[id="gfpdf_settings[default_template]"]')

    // General Settings - Default Font field
    this.defaultFontSelectBox = Selector('#gfpdf-settings-field-wrapper-default_font').find('[id="gfpdf_settings[default_font]"]')

    // General Settings - Default Paper Size field
    this.defaultPaperSizeSelectBox = Selector('#gfpdf-settings-field-wrapper-default_pdf_size').find('[id="gfpdf_settings[default_pdf_size]"]')

    // General Settings - Reverse Text (RTL) field
    this.reverseTextRtlCheckbox = Selector('#gfpdf-settings-field-wrapper-default_rtl').find('[id="gfpdf_settings[default_rtl]"]')

    // General Settings - Default Font Size field
    this.defaultFontSizeInputBox = Selector('#gfpdf-settings-field-wrapper-default_font_size').find('[id="gfpdf_settings[default_font_size]"]')

    // General Settings - Default Font Color field
    this.defaultFontColorSelectButton = Selector('#gfpdf-settings-field-wrapper-default_font_colour').find('button').withText('Select Color')
    this.defaultFontColorInputBox = Selector('#gfpdf-settings-field-wrapper-default_font_colour').find('[id="gfpdf_settings[default_font_colour]"]')

    // General Settings - Entry View field
    this.entryViewViewOption = Selector('#gfpdf-fieldset-default_action').find('[id="gfpdf_settings[default_action][View]"]')
    this.entryViewDownlaodOption = Selector('#gfpdf-fieldset-default_action').find('[id="gfpdf_settings[default_action][Download]"]')

    // General Settings - Background Processing field
    this.backgroundProcessingCheckbox = Selector('#gfpdf-fieldset-background_processing').find('[id="gfpdf_settings[background_processing]"]')

    // General Settings - Debug Mode field
    this.debugModeCheckbox = Selector('#gfpdf-fieldset-debug_mode').find('[id="gfpdf_settings[debug_mode]"]')

    // General Settings - Logged Out Timeout field
    this.loggedOutTimeoutInputBox = Selector('#gfpdf-fieldset-gfpdf_settings_general_security').find('[id="gfpdf_settings[logged_out_timeout]"]')

    // General Settings - Default Owner Restrictions field
    this.defaultOwnerRestrictionsCheckbox = Selector('#gfpdf-settings-field-wrapper-default_restrict_owner').find('[id="gfpdf_settings[default_restrict_owner]"]')

    // General Settings - User Restriction field
    this.userRestrictionOption = Selector('#gfpdf-settings-field-wrapper-admin_capabilities').find('input')

    this.entries = Selector('#the-list').find('tr').withText('Sample 2').find('span').withText('Entries')
    this.list = Selector('.gf-locking ').withText('Sample 2')
    this.template = Selector('.alternate')
    this.testTemplateDetailsLink = Selector('.theme[data-slug="test-template"]').find('span').withText('Template Details')

    this.addNewTemplate = Selector('input').withAttribute('type', 'file')
    this.saveSettings = Selector('#submit-and-promo-container').find('input')

    // PDF entries section
    this.viewEntryItem = Selector('a').withAttribute('aria-label', 'View this entry')
  }

  async navigateSettingsTab (text) {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .typeText('#user_login', 'admin', { paste: true })
      .typeText('#user_pass', 'password', { paste: true })
      .click('#wp-submit')
  }

  async navigatePdfEntries (text) {
    await t
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }
}

export default General
