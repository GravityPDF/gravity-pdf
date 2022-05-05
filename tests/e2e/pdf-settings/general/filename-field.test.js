import {
  fieldLabel,
  mergeTagsWrapper,
  filenameGroupOption,
  filenameOptionItem
} from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF general settings - Filename field test`

test('should display \'Filename\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Filename (required)').exists).ok()
    .expect(run.filenameInputBox.exists).ok()
    .expect(run.filenameMergeTagsOptionList.exists).ok()
})

test('should check that merge tags list option exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.filenameMergeTagsOptionList)

  // Assertions
  await t
    .expect(mergeTagsWrapper('gfpdf-settings-field-wrapper-filename').find('li').count).gt(0)
    .expect(filenameGroupOption('Optional form fields').exists).ok()
    .expect(filenameOptionItem('Text').exists).ok()
    .expect(filenameOptionItem('Name (Prefix)').exists).ok()
    .expect(filenameGroupOption('Other').exists).ok()
    .expect(filenameOptionItem('User IP Address').exists).ok()
    .expect(filenameOptionItem('Date (mm/dd/yyyy)').exists).ok()
    .expect(filenameGroupOption('Custom').exists).ok()
    .expect(filenameOptionItem('PDF: Sample').exists).ok()
})

test('should save selected merge tags', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.filenameInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.filenameMergeTagsOptionList)
    .click(filenameOptionItem('Text'))
    .click(run.filenameMergeTagsOptionList)
    .click(filenameOptionItem('Name (Prefix)'))
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
