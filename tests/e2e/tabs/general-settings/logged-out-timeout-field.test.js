import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Logged out timeout field test`

test('should display \'Logged Out Timeout\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Logged Out Timeout').exists).ok()
    .expect(fieldDescription('Limit how long a logged out users has direct access to the PDF after completing the form. Set to 0 to disable time limit (not recommended).', 'label').exists).ok()
    .expect(run.loggedOutTimeoutInputBox.exists).ok()
})

test('should save updated value for time limit', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.securityCollapsiblePanel)
    .click(run.loggedOutTimeoutInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.loggedOutTimeoutInputBox, '34', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.loggedOutTimeoutInputBox.value).eql('34')
})
