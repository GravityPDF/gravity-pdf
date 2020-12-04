import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF appearance settings - Font size field test`

test('should display \'Font Size\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Font Size').exists).ok()
    .expect(fieldDescription('Set the font size to use in the PDF.', 'label').exists).ok()
    .expect(run.fontSizeInputBox.exists).ok()
})

test('should save selected font size', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.appearanceCollapsiblePanel)
    .click(run.fontSizeInputBox)
  await t
    .pressKey('ctrl+a')
    .pressKey('backspace')
  await t
    .typeText(run.fontSizeInputBox, '15', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.fontSizeInputBox.value).eql('15')
})
