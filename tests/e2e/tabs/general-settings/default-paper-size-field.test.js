import {
  fieldLabel,
  selectBox,
  dropdownOptionGroup,
  dropdownOption
} from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Default paper size field test`

test('should display \'Default Paper Size\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Paper Size').exists).ok()
    .expect(run.defaultPaperSizeSelectBox.exists).ok()
})

test('should display a dropdown of paper sizes option', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(run.defaultPaperSizeSelectBox)

  // Assertions
  await t
    .expect(dropdownOptionGroup('Common Sizes').exists).ok()
    .expect(dropdownOption('A4 (210 x 297mm)').exists).ok()
    .expect(dropdownOption('Letter (8.5 x 11in)').exists).ok()
    .expect(dropdownOption('Custom Paper Size').exists).ok()
    .expect(dropdownOptionGroup('"A" Sizes').exists).ok()
    .expect(dropdownOption('A0 (841 x 1189mm)').exists).ok()
    .expect(dropdownOption('A1 (594 x 841mm)').exists).ok()
    .expect(dropdownOptionGroup('"B" Sizes').exists).ok()
    .expect(dropdownOption('B0 (1414 x 1000mm)').exists).ok()
    .expect(dropdownOption('B1 (1000 x 707mm)').exists).ok()
    .expect(dropdownOptionGroup('"C" Sizes').exists).ok()
    .expect(dropdownOption('C0 (1297 x 917mm)').exists).ok()
    .expect(dropdownOption('C1 (917 x 648mm)').exists).ok()
    .expect(dropdownOptionGroup('"RA" and "SRA" Sizes').exists).ok()
    .expect(dropdownOption('RA0 (860 x 1220mm)').exists).ok()
    .expect(dropdownOption('RA1 (610 x 860mm)').exists).ok()
})

test('should save selected paper size', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(run.defaultPaperSizeSelectBox)
  await t.click(dropdownOption('Letter (8.5 x 11in)'))
  await t.click(run.saveSettings)

  // Assertions
  await t.expect(run.defaultPaperSizeSelectBox.value).eql('LETTER')
})

test('should save selected custom paper size', async t => {
  // Selectors
  const widthInputBox = selectBox('regular-text gfpdf_settings_default_custom_pdf_size', 'gfpdf_settings[default_custom_pdf_size]_width')
  const heightInputBox = selectBox('regular-text gfpdf_settings_default_custom_pdf_size', 'gfpdf_settings[default_custom_pdf_size]_height')
  const measurementInputBox = selectBox('gfpdf_settings_default_custom_pdf_size ', 'gfpdf_settings[default_custom_pdf_size]_measurement')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.defaultPaperSizeSelectBox)
    .click(dropdownOption('Custom Paper Size'))
    .click(widthInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(widthInputBox, '2', { paste: true })
    .click(heightInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(heightInputBox, '4', { paste: true })
    .click(measurementInputBox)
    .click(dropdownOption('inches'))
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.defaultPaperSizeSelectBox.value).eql('CUSTOM')
    .expect(run.customPaperSizeLabel.exists).ok()
    .expect(widthInputBox.value).eql('2')
    .expect(heightInputBox.value).eql('4')
    .expect(measurementInputBox.value).eql('inches')
})
