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

fixture`PDF template settings - Footer field test`

test('should display \'Footer\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Footer').exists).ok()
    .expect(fieldDescription('The footer is included at the bottom of every page. For simple text footers use the left, center and right alignment buttons in the editor. For simple columns try this HTML table snippet. Use the special {PAGENO} and {nbpg} tags to display page numbering.', 'label').exists).ok()
    .expect(addMediaButton('gfpdf-settings-field-wrapper-footer', 'Add Media').exists).ok()
    .expect(run.footerWpEditorBox.exists).ok()
})

test('should check that upload media manager exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(addMediaButton('gfpdf-settings-field-wrapper-footer', 'Add Media'))

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

test('should save added footer data', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(addMediaButton('gfpdf-settings-field-wrapper-footer', 'Add Media'))
    .click(mediaManager.uploadFilesPanelLink)
    .click(mediaManager.selectFilesButton)
    .setFilesToUpload(mediaManager.uploadMediaFilesInputBox, mediaManager.backgroundImage)
    .click(mediaManager.insertIntoPostButton)
    .click(run.footerWpEditorBoxTextPanelLink)
    .typeText(run.footerWpEditorBoxContentArea, 'test', { paste: true })
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.footerWpEditorBoxContentArea.withText('/background-image-300x193.jpg').exists).ok()
    .expect(run.footerWpEditorBoxContentArea.withText('test').exists).ok()
})

test('should delete/reset footer field content', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(addMediaButton('gfpdf-settings-field-wrapper-footer', 'Add Media'))
    .click(mediaManager.uploadedMediaFile)
    .click(mediaManager.deleteFile)
    .expect(mediaManager.uploadedMediaFile.count).eql(0)
    .click(mediaManager.mediaCloseDialog)
    .click(run.footerWpEditorBoxTextPanelLink)
    .click(run.footerWpEditorBoxContentArea)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.saveSettings)
    .expect(run.footerWpEditorBoxContentArea.withText('/background-image-300x193.jpg').exists).notOk()
    .expect(run.footerWpEditorBoxContentArea.withText('test').exists).notOk()
})
