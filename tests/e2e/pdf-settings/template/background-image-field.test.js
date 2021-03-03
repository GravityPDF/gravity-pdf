import { fieldLabel, fieldDescription, mediaManagerTitle } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'
import MediaManager from '../../utilities/page-model/helpers/media-manager'

const run = new Pdf()
const mediaManager = new MediaManager()

fixture`PDF template settings - Background image field test`

test('should display \'Background Image\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Background Image').exists).ok()
    .expect(fieldDescription('The background image is included on all pages. For optimal results, use an image the same dimensions as the paper size and run it through an image optimization tool before upload.', 'label').exists).ok()
    .expect(run.backgroundImageUploadBox.exists).ok()
    .expect(run.backgroundImageUploadFileButton.exists).ok()
})

test('should check that upload media manager exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(run.backgroundImageUploadFileButton)

  // Assertions
  await t
    .expect(mediaManager.mediaModalContainer.exists).ok()
    .expect(mediaManager.mediaCloseDialog.exists).ok()
    .expect(mediaManagerTitle('Select Media').exists).ok()
    .expect(mediaManager.uploadFilesPanelLink.exists).ok()
    .expect(mediaManager.mediaLibraryPanelLink.exists).ok()
    .expect(mediaManager.dropInstructionsText.exists).ok()
    .expect(mediaManager.selectFilesButton.exists).ok()
    .expect(mediaManager.maxUploadSizeText.exists).ok()
})

test('should save uploaded file', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(run.backgroundImageUploadFileButton)
    .click(mediaManager.uploadFilesPanelLink)
    .click(mediaManager.selectFilesButton)
    .setFilesToUpload(mediaManager.uploadMediaFilesInputBox, mediaManager.backgroundImage)
    .click(mediaManager.selectMediaButton)
    .click(run.saveSettings)
    .expect(run.backgroundImageUploadBox.value).contains('background-image.jpg')
    .click(run.backgroundImageUploadBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.saveSettings)
    .expect(run.backgroundImageUploadBox.value).eql('')
})

test('should delete uploaded media file', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(run.backgroundImageUploadFileButton)
    .click(mediaManager.uploadedMediaFile)
    .click(mediaManager.deleteFile)
    .expect(mediaManager.uploadedMediaFile.count).eql(0)
    .click(mediaManager.mediaCloseDialog)
    .click(run.saveSettings)
    .click(run.backgroundImageUploadFileButton)
    .expect(mediaManager.uploadedMediaFile.count).eql(0)
})
