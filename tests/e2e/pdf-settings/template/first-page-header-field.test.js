import {
  fieldLabel,
  mediaManagerTitle,
  addMediaButton
} from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'
import MediaManager from '../../utilities/page-model/helpers/media-manager'

const run = new Pdf()
const mediaManager = new MediaManager()

fixture`PDF template settings - First page header field test`

test('should display \'First Page Header\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.firstPageHeaderCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('First Page Header').exists).ok()
    .expect(run.firstPageHeaderCheckbox.exists).ok()
    .expect(addMediaButton('gfpdf-settings-field-wrapper-first_header', 'Add Media').exists).ok()
    .expect(run.firstPageHeaderWpEditorBox.exists).ok()
})

test('should check that upload media manager exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.firstPageHeaderCheckbox)
    .click(addMediaButton('gfpdf-settings-field-wrapper-first_header', 'Add Media'))

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
    .click(run.firstPageHeaderCheckbox)
    .click(addMediaButton('gfpdf-settings-field-wrapper-first_header', 'Add Media'))
    .click(mediaManager.uploadFilesPanelLink)
    .click(mediaManager.selectFilesButton)
    .setFilesToUpload(mediaManager.uploadMediaFilesInputBox, mediaManager.backgroundImage)
    .click(mediaManager.insertIntoPostButton)
    .click(run.firstPageHeaderWpEditorBoxTextPanelLink)
    .typeText(run.firstPageHeaderWpEditorBoxContentArea, 'test', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.firstPageHeaderWpEditorBoxContentArea.withText('/background-image-300x193.jpg').exists).ok()
    .expect(run.firstPageHeaderWpEditorBoxContentArea.withText('test').exists).ok()
})

test('should delete/reset \'First Page Header\' field content', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(addMediaButton('gfpdf-settings-field-wrapper-first_header', 'Add Media'))
    .click(mediaManager.uploadedMediaFile)
    .click(mediaManager.deleteFile)
    .expect(mediaManager.uploadedMediaFile.count).eql(0)
    .click(mediaManager.mediaCloseDialog)
    .click(run.firstPageHeaderWpEditorBoxTextPanelLink)
    .click(run.firstPageHeaderWpEditorBoxContentArea)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.saveSettings)
    .expect(run.firstPageHeaderCheckbox.checked).notOk()
})
