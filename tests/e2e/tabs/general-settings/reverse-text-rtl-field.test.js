import { fieldLabel } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Reverse text (RTL) field test`

test('should display \'Reverse Text (RTL)\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Reverse Text (RTL)').exists).ok()
    .expect(run.reverseTextRtlCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.reverseTextRtlCheckbox)
    .click(run.saveSettings)
    .expect(run.reverseTextRtlCheckbox.checked).ok()
    .click(run.reverseTextRtlCheckbox)
    .click(run.saveSettings)
    .expect(run.reverseTextRtlCheckbox.checked).notOk()
})
