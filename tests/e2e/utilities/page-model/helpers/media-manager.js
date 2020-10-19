import { Selector } from 'testcafe'

class MediaManager {
  constructor () {
    this.mediaModalContainer = Selector('.supports-drag-drop').find('[class^="media-modal wp-core-ui"]')
    this.mediaCloseDialog = Selector('.media-modal').find('[class^="media-modal-close"]')
    this.uploadFilesPanelLink = Selector('.media-frame-router').find('[id="menu-item-upload"]')
    this.mediaLibraryPanelLink = Selector('.media-frame-router').find('[id="menu-item-browse"]')
    this.dropInstructionsText = Selector('.media-frame-content').withText('Drop files to upload')
    this.selectFilesButton = Selector('.upload-ui').find('button').withText('Select Files')
    this.maxUploadSizeText = Selector('.media-frame-content').find('[class^="max-upload-size"]')
    this.uploadMediaFilesInputBox = Selector('.media-modal-content').find('input').withAttribute('type', 'file')
    this.uploadedMediaFile = Selector('.media-frame-content').find('li').withAttribute('role', 'checkbox')
    this.deleteFile = Selector('.media-sidebar').find('button').withText('Delete permanently')

    // Buttons
    this.selectMediaButton = Selector('.media-toolbar').find('button').withText('Select Media')
    this.insertIntoPostButton = Selector('.media-toolbar').find('button').withText('Insert into post')

    // Resources
    this.backgroundImage = '../../utilities/resources/background-image.jpg'
  }
}

export default MediaManager
