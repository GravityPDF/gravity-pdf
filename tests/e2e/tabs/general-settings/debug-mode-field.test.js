import { fieldHeaderTitle } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Debug mode field test`

test('should display \'Debug Mode\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Debug Mode').exists).ok()
    .expect(run.debugModeCheckbox.exists).ok()
})

test('should save toggled \'Debug Mode\' value', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.debugModeCheckbox)
    .click(run.saveSettings)
    .expect(run.debugModeCheckbox.checked).ok()
    .click(run.debugModeCheckbox)
    .click(run.saveSettings)
    .expect(run.debugModeCheckbox.checked).notOk()
})
