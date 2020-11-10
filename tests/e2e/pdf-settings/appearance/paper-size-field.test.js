import {
  fieldLabel,
  fieldDescription,
  selectBox,
  dropdownOptionGroup,
  dropdownOption
} from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF appearance settings - Paper size field test`

test('should display \'Paper Size\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Paper Size').exists).ok()
    .expect(fieldDescription('Set the paper size used when generating PDFs.', 'label').exists).ok()
    .expect(run.paperSizeSelectBox.exists).ok()
})

test('should display a dropdown of paper sizes option', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t.click(run.appearanceCollapsiblePanel)

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
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.appearanceCollapsiblePanel)
    .click(run.paperSizeSelectBox)
    .click(dropdownOption('Letter (8.5 x 11in)'))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.paperSizeSelectBox.value).eql('LETTER')
})

test('should save selected custom paper size', async t => {
  // Selectors
  const widthInputBox = selectBox('small-text gfpdf_settings_custom_pdf_size', 'gfpdf_settings[custom_pdf_size]_width')
  const heightInputBox = selectBox('small-text gfpdf_settings_custom_pdf_size', 'gfpdf_settings[custom_pdf_size]_height')
  const measurementInputBox = selectBox('gfpdf_settings_custom_pdf_size ', 'gfpdf_settings[custom_pdf_size]_measurement')

  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.appearanceCollapsiblePanel)
    .click(run.paperSizeSelectBox)
    .click(dropdownOption('Custom Paper Size'))
    .click(widthInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(widthInputBox, '2', { paste: true })
    .click(heightInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(heightInputBox, '5', { paste: true })
    .click(measurementInputBox)
    .click(dropdownOption('inches'))
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.paperSizeSelectBox.value).eql('CUSTOM')
    .expect(fieldLabel('Custom Paper Size').exists).ok()
    .expect(fieldDescription('Control the exact paper size. Can be set in millimeters or inches.', 'label').exists).ok()
    .expect(widthInputBox.value).eql('2')
    .expect(heightInputBox.value).eql('5')
    .expect(measurementInputBox.value).eql('inches')
})
