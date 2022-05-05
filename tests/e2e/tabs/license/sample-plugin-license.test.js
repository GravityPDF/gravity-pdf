import License from '../../utilities/page-model/tabs/license'

const run = new License()

fixture`License tab - Sample plugin / extension test`

test('should display error message for invalid license key', async t => {
  // Actions
  await run.navigateCoreBooster('gf_settings&subview=PDF&tab=license')
  await t
    .typeText(run.samplePluginInputBox, run.invalidLicenseKey, { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.invalidLicenseKeyMessage.exists).ok()
    .expect(run.deactivateLinkMessage.exists).notOk()
})

test('should display success message and deactivation option for active license key', async t => {
  // Actions
  await run.navigateCoreBooster('gf_settings&subview=PDF&tab=license')
  await t
    .typeText(run.samplePluginInputBox, run.validLicenseKey, { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.successMessage.exists).ok()
    .expect(run.deactivateLinkMessage.exists).ok()
})

test('should deactivate license and display deactivated message', async t => {
  // Actions
  await run.navigateCoreBooster('gf_settings&subview=PDF&tab=license')
  await t.click(run.deactivateLink)

  // Assertions
  await t.expect(run.deactivatedMessage.exists).ok()
})
