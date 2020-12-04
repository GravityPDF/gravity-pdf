import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../auth'
import { button, link } from './field'

class Page {
  constructor () {
    this.closePopupButton = Selector('button').withAttribute('aria-label', 'Close dialog')
    this.titleField = Selector('.editor-post-title').find('textarea').withAttribute('placeholder', 'Add title')
    this.addBlockIcon = Selector('button').withAttribute('aria-label', 'Add block')
    this.searchBlock = Selector('input').withAttribute('placeholder', 'Search for a block')
    this.shortcodeLink = Selector('button.editor-block-list-item-shortcode')
    this.shortcodeTextarea = Selector('textarea').withAttribute('placeholder', 'Write shortcode here…')
    this.trashLink = Selector('a').withAttribute('aria-label', 'Move “Test page” to the Trash')
    this.publishButton = Selector('.edit-post-header__settings').find('button').withText('Publish')
    this.confirmPublishButton = Selector('.editor-post-publish-panel__header').find('button').withText('Publish')
  }

  async navigatePage () {
    await t
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
  }

  async addNewPage () {
    await this.navigatePage()
    await t
      .click(link('.wrap', 'Add New'))
      .click(this.closePopupButton)
      .typeText(this.titleField, 'Test page', { paste: true })
      .click(this.publishButton)
      .click(this.confirmPublishButton)
  }
}

export default Page
