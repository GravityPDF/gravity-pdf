import License from '../../page-model/global-settings/license/license'

const run = new License()

fixture`License Tab - Core Booster Activate Invalid License Test`

test('should display error icon and error message for invalid license key', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=license')
  await t
    .typeText(run.licenseInputField, run.invalidLicenseKey, { paste: true })
    .click(run.saveButton)

  // Assertions
  await t
    .expect(run.errorIcon.exists).ok()
    .expect(run.invalidLicenseKeyMessage.exists).ok()
})
