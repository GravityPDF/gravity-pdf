import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Default font size field test`

test('should display \'Default Font Size\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Font Size').exists).ok()
    .expect(fieldDescription('Set the default font size used in PDFs.', 'label').exists).ok()
    .expect(run.defaultFontSizeInputBox.exists).ok()
})

test('should save selected font size', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(run.defaultFontSizeInputBox)
  await t
    .pressKey('ctrl+a')
    .pressKey('backspace')
  await t
    .typeText(run.defaultFontSizeInputBox, '15', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.defaultFontSizeInputBox.value).eql('15')
})
