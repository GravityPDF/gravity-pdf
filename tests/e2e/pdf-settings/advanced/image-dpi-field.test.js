import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Image DPI field test`

test('should display \'Image DPI\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Image DPI').exists).ok()
    .expect(run.imageDpiInputBox.exists).ok()
})

test('should save selected image DPI value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.imageDpiInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.imageDpiInputBox, '98', { paste: true })
    .click(run.saveSettings)
    .expect(run.imageDpiInputBox.value).eql('98')
    .click(run.imageDpiInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.imageDpiInputBox, '96', { paste: true })
    .click(run.saveSettings)
    .expect(run.imageDpiInputBox.value).eql('96')
})
