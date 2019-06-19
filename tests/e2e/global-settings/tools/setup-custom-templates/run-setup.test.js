import SetupCustomTemplates
  from '../../../page-model/global-settings/tools/setup-custom-templates/setup-custom-templates'

const run = new SetupCustomTemplates()

fixture`Tools Tab - Setup Custom Templates Test`

test('should open Setup Custom Templates popup box and successMessage for first setup', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')

  // Assertions
  await run.successUpdateMessage.exists ? (
    await t.expect(run.successUpdateMessage.exists).ok()
  ) : (
    await t
      .expect(run.popUpBox.filterVisible().count).eql(1)
      .expect(run.continueButton.exists).ok()
      .expect(run.cancelButton.exists).ok()
  )
})

test('should open Setup Custom Templates popup box that can be close / cancel', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
  await t
    .click(run.cancelButton)
    .expect(run.popUpBox.filterHidden().count).eql(1)
})

test('should run setup for custom templates and display installation success message', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
  await t.click(run.continueButton)

  // Assertions
  await t
    .expect(run.popUpBox.exists).ok()
    .expect(run.continueButton.exists).ok()
    .expect(run.successUpdateMessage.exists).ok()
})
