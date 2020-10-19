import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Default owner restrictions field test`

test('should display \'Default Owner Restrictions\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Owner Restrictions').exists).ok()
    .expect(fieldDescription('Set the default PDF owner permissions. When enabled, the original entry owner will NOT be able to view the PDFs (unless they have a User Restriction capability).', 'span').exists).ok()
    .expect(run.defaultOwnerRestrictionsCheckbox.exists).ok()
})

test('should save toggled \'Default Owner Restrictions\' value', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.securityCollapsiblePanel)
    .click(run.defaultOwnerRestrictionsCheckbox)
    .click(run.saveSettings)
    .expect(run.defaultOwnerRestrictionsCheckbox.checked).ok()
    .click(run.securityCollapsiblePanel)
    .click(run.defaultOwnerRestrictionsCheckbox)
    .click(run.saveSettings)
    .expect(run.defaultOwnerRestrictionsCheckbox.checked).notOk()
})
