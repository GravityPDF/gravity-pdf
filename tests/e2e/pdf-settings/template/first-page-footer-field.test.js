import {
  fieldDescription,
  fieldLabel,
  mediaManagerTitle,
  addMediaButton
} from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'
import MediaManager from '../../utilities/page-model/helpers/media-manager'

const run = new Pdf()
const mediaManager = new MediaManager()

fixture`PDF template settings - First page footer field test`

test('should display \'First Page Footer\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.firstPageFooterCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('First Page Footer').exists).ok()
    .expect(run.firstPageFooterCheckbox.exists).ok()
    .expect(fieldDescription('Use different footer on first page of PDF?', 'label').exists).ok()
    .expect(fieldDescription('Override the footer on the first page of the PDF.', 'label').exists).ok()
    .expect(addMediaButton('gfpdf-settings-field-wrapper-first_footer', 'Add Media').exists).ok()
    .expect(run.firstPageFooterWpEditorBox.exists).ok()
})

test('should check that upload media manager exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.firstPageFooterCheckbox)
    .click(addMediaButton('gfpdf-settings-field-wrapper-first_footer', 'Add Media'))

  // Assertions
  await t
    .expect(mediaManager.mediaModalContainer.exists).ok()
    .expect(mediaManager.mediaCloseDialog.exists).ok()
    .expect(mediaManagerTitle('Add media').exists).ok()
    .expect(mediaManager.uploadFilesPanelLink.exists).ok()
    .expect(mediaManager.mediaLibraryPanelLink.exists).ok()
    .expect(mediaManager.dropInstructionsText.exists).ok()
    .expect(mediaManager.selectFilesButton.exists).ok()
    .expect(mediaManager.maxUploadSizeText.exists).ok()
})

test('should save checkbox toggled value', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.firstPageFooterCheckbox)
    .click(addMediaButton('gfpdf-settings-field-wrapper-first_footer', 'Add Media'))
    .click(mediaManager.uploadFilesPanelLink)
    .click(mediaManager.selectFilesButton)
    .setFilesToUpload(mediaManager.uploadMediaFilesInputBox, mediaManager.backgroundImage)
    .click(mediaManager.insertIntoPostButton)
    .click(run.firstPageFooterWpEditorBoxTextPanelLink)
    .typeText(run.firstPageFooterWpEditorBoxContentArea, 'test', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.firstPageFooterWpEditorBoxContentArea.withText('/background-image-300x193.jpg').exists).ok()
    .expect(run.firstPageFooterWpEditorBoxContentArea.withText('test').exists).ok()
})

test('should delete/reset \'First Page Footer\' field content', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(addMediaButton('gfpdf-settings-field-wrapper-first_footer', 'Add Media'))
    .click(mediaManager.uploadedMediaFile)
    .click(mediaManager.deleteFile)
    .expect(mediaManager.uploadedMediaFile.count).eql(0)
    .click(mediaManager.mediaCloseDialog)
    .click(run.firstPageFooterWpEditorBoxTextPanelLink)
    .click(run.firstPageFooterWpEditorBoxContentArea)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.saveSettings)
    .expect(run.firstPageFooterCheckbox.checked).notOk()
})
