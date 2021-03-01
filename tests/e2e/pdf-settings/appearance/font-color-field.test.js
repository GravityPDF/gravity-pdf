import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, button } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF appearance settings - Font color field test`

test('should display \'Font Color\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.appearanceCollapsiblePanel)
    .click(button('Select Color'))

  // Assertions
  await t
    .expect(fieldLabel('Font Color').exists).ok()
    .expect(fieldDescription('Set the font color to use in the PDF.', 'label').exists).ok()
    .expect(run.fontColorSelectButton.exists).ok()
    .expect(run.fontColorInputBox.exists).ok()
    .expect(run.fontColorWpPickerContainerActive.exists).ok()
    .expect(run.fontColorWpColorPickerBox.exists).ok()
})

test('should save selected font color', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.appearanceCollapsiblePanel)
    .click(run.fontColorSelectButton)
    .click(Selector('#gfpdf-settings-field-wrapper-font_colour').find('a').withAttribute('class', 'iris-palette').nth(3))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.fontColorInputBox.value).eql('#dd9933')
})
