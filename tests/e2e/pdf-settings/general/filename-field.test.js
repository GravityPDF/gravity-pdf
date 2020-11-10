import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF general settings - Filename field test`

test('should display \'Filename\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Filename (required)').exists).ok()
    .expect(fieldDescription('Set the filename for the generated PDF (excluding the .pdf extension). Mergetags are supported, and invalid characters / \\ " * ? | : < > are automatically converted to an underscore.', 'label').exists).ok()
    .expect(run.filenameInputBox.exists).ok()
    .expect(run.filenameMergeTagsOptionList.exists).ok()
})

test('should check that merge tags list option exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t.click(run.filenameMergeTagsOptionList)

  // Assertions
  await t
    .expect(run.listItems.count).eql(26)
    .expect(run.groupHeader1.exists).ok()
    .expect(run.optionA.exists).ok()
    .expect(run.optionB.exists).ok()
    .expect(run.groupHeader2.exists).ok()
    .expect(run.optionC.exists).ok()
    .expect(run.optionD.exists).ok()
    .expect(run.groupHeader3.exists).ok()
    .expect(run.optionE.exists).ok()
})

test('should save selected merge tags', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.filenameInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.filenameMergeTagsOptionList)
    .click(run.optionA)
    .click(run.filenameMergeTagsOptionList)
    .click(run.optionB)
    .click(run.saveSettings)
    .expect(run.filenameInputBox.value).eql('{Text:1}{Name (Prefix):2.2}')
    .click(run.filenameInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.filenameInputBox, '{Text:1}', { paste: true })
    .click(run.saveSettings)
    .expect(run.filenameInputBox.value).eql('{Text:1}')
    .click(run.filenameInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.filenameInputBox, 'sample', { paste: true })
    .click(run.saveSettings)
    .expect(run.filenameInputBox.value).eql('sample')
})
