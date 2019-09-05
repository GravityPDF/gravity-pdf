import License from '../../page-model/global-settings/license/license'

const run = new License()

fixture`License Tab - Core Booster Deactivate License Test`

test('should deactivate license and display deactivated message', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=license')
  await t
    .typeText(run.licenseInputField, run.validLicenseKey, { paste: true })
    .click(run.saveButton)
    .wait(3000)
    .click(run.deactivateLink)
    .wait(3000)
    .expect(run.deactivatedMessage.exists).ok()
})
