import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, dropdownOptionGroup, dropdownOption } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Advanced Options Field Test`

test('should display Show Advanced Options field link', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t.click(fieldLabel('Show Advanced Options...', 'a'))

  // Assertions
  await t.expect(run.advancedOptionsField.filterVisible().count).eql(1)
})

test('should display Show Advanced Options field', async t => {
  // Get selectors
  const selectBox = Selector('#gfpdf_settings_admin_capabilities__chosen')
  const dropDownList = Selector('.chosen-results')
  const enable = Selector('div').find('[class^="gfpdf_settings_default_restrict_owner"][value="Yes"]')
  const disable = Selector('div').find('[class^="gfpdf_settings_default_restrict_owner"][value="No"]')
  const inputBox = Selector('#gfpdf_settings\\[logged_out_timeout\\]')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t.click(fieldLabel('Show Advanced Options...', 'a'))

  // Assertions
  await t
    .expect(fieldLabel('Security Settings', 'span').exists).ok()
    .expect(fieldLabel('User Restriction').exists).ok()
    .expect(selectBox.exists).ok()
    .expect(dropDownList.exists).ok()
    .expect(dropdownOptionGroup('Gravity Forms Capabilities').exists).ok()
    .expect(dropdownOption('gravityforms_create_form').exists).ok()
    .expect(dropdownOption('gravityforms_delete_forms').exists).ok()
    .expect(dropdownOptionGroup('Active WordPress Capabilities').exists).ok()
    .expect(dropdownOption('activate_plugins').exists).ok()
    .expect(dropdownOption('create_users').exists).ok()
    .expect(fieldDescription('Restrict PDF access to users with any of these capabilities. The Administrator Role always has full access.', 'label').exists).ok()
    .expect(fieldLabel('Default Owner Restrictions').exists).ok()
    .expect(enable.exists).ok()
    .expect(disable.exists).ok()
    .expect(fieldDescription('Set the default PDF owner permissions. When enabled, the original entry owner will NOT be able to view the PDFs (unless they have one of the above capabilities).', 'label').exists).ok()
    .expect(fieldLabel('Logged Out Timeout').exists).ok()
    .expect(inputBox.exists).ok()
    .expect(fieldDescription('Limit how long a logged out users has direct access to the PDF after completing the form. Set to 0 to disable time limit (not recommended).', 'label').exists).ok()
})

test('should hide Show Advanced Options field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(fieldLabel('Show Advanced Options...', 'a'))
    .click(fieldLabel('Hide Advanced Options...', 'a'))

  // Assertions
  await t
    .expect(fieldLabel('Hide Advanced Options...', 'a').exists).ok()
    .wait(1000)
    .expect(run.advancedOptionsField.filterHidden().count).eql(1)
})
