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

fixture`PDF template settings - Header field test`

test('should display \'Header\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Header').exists).ok()
    .expect(fieldDescription('The header is included at the top of each page. For simple columns try this HTML table snippet.', 'label').exists).ok()
    .expect(addMediaButton('gfpdf-settings-field-wrapper-header', 'Add Media').exists).ok()
    .expect(run.headerWpEditorBox.exists).ok()
})

test('should check that upload media manager exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(addMediaButton('gfpdf-settings-field-wrapper-header', 'Add Media'))

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

test('should save added header data', async t => {
  // Actions
  await t.setTestSpeed(0.4)
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(addMediaButton('gfpdf-settings-field-wrapper-header', 'Add Media'))
    .click(mediaManager.uploadFilesPanelLink)
    .click(mediaManager.selectFilesButton)
    .setFilesToUpload(mediaManager.uploadMediaFilesInputBox, mediaManager.backgroundImage)
    .click(mediaManager.insertIntoPostButton)
    .click(run.headerWpEditorBoxTextPanelLink)
    .typeText(run.headerWpEditorBoxContentArea, 'test', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.headerWpEditorBoxContentArea.withText('/background-image-300x193.jpg').exists).ok()
    .expect(run.headerWpEditorBoxContentArea.withText('test').exists).ok()
})

test('should delete/reset header field content', async t => {
  // Actions & Assertions
  await t.setTestSpeed(0.4)
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(addMediaButton('gfpdf-settings-field-wrapper-header', 'Add Media'))
    .click(mediaManager.uploadedMediaFile)
    .click(mediaManager.deleteFile)
    .expect(mediaManager.uploadedMediaFile.count).eql(0)
    .click(mediaManager.mediaCloseDialog)
    .click(run.headerWpEditorBoxTextPanelLink)
    .click(run.headerWpEditorBoxContentArea)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.saveSettings)
    .expect(run.headerWpEditorBoxContentArea.withText('/background-image-300x193.jpg').exists).notOk()
    .expect(run.headerWpEditorBoxContentArea.withText('test').exists).notOk()
})
