import { fieldHeaderTitle, fieldDescription } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Background processing field test`

test('should display \'Background Processing\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Background Processing').exists).ok()
    .expect(fieldDescription('When enable, form submission and resending notifications with PDFs are handled in a background task. Requires Background tasks to be enabled.').exists).ok()
    .expect(run.backgroundProcessingCheckbox.exists).ok()
})

test('should save toggled \'Background Processing\' value', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.backgroundProcessingCheckbox)
    .click(run.saveSettings)
  await t.expect(run.backgroundProcessingCheckbox.checked).ok()
  await t
    .click(run.backgroundProcessingCheckbox)
    .click(run.saveSettings)
  await t.expect(run.backgroundProcessingCheckbox.checked).notOk()
})
