import License from '../../page-model/global-settings/license/license'

const run = new License()

fixture`License Tab - Core Booster Activate Valid License Test`

test('should display success icon and deactivation option for active license key', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=license')
  await t
    .typeText(run.licenseInputField, run.validLicenseKey, { paste: true })
    .click(run.saveButton)

  // Assertions
  await t
    .expect(run.successIcon.exists).ok()
    .expect(run.deactivateLinkMessage.exists).ok()
})
