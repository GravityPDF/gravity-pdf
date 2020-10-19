import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Default font color field test`

test('should display \'Default Font Color\' field', async t => {
  // Selectors
  const defaultFontColorWpPickerContainerActive = Selector('#gfpdf-settings-field-wrapper-default_font_colour').find('[class^="wp-picker-container wp-picker-active"]')
  const defaultFontColorWpColorPickerBox = Selector('#gfpdf-settings-field-wrapper-default_font_colour').find('[class^="iris-picker iris-border"]')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(run.defaultFontColorSelectButton)

  // Assertions
  await t
    .expect(fieldLabel('Default Font Color').exists).ok()
    .expect(fieldDescription('Set the default font color used in PDFs.', 'label').exists).ok()
    .expect(run.defaultFontColorSelectButton.exists).ok()
    .expect(run.defaultFontColorInputBox.exists).ok()
    .expect(defaultFontColorWpPickerContainerActive.exists).ok()
    .expect(defaultFontColorWpColorPickerBox.exists).ok()
})

test('should save selected font color', async t => {
  // Selectors
  const colorBoxPicker = Selector('#gfpdf-settings-field-wrapper-default_font_colour').find('a').withAttribute('class', 'iris-palette')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.defaultFontColorSelectButton)
    .click(colorBoxPicker.nth(4))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.defaultFontColorInputBox.value).eql('#eeee22')
})
