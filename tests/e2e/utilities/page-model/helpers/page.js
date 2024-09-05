import { Selector, t } from 'testcafe'
import { baseURL } from '../../../auth'
import { link } from './field'

class Page {
  constructor () {
    this.testPageLink = link('#the-list', 'Test-page')
    this.closePopupButton = Selector('button').withAttribute('aria-label', 'Close dialog')
    this.closePopupPattern = Selector('button').withAttribute('aria-label', 'Close')
    this.titleField = Selector('.editor-post-title__input')
    this.addBlockIcon = Selector('button.editor-document-tools__inserter-toggle')
    this.searchBlock = Selector('.block-editor-inserter__search').find('input').withAttribute('type', 'search')
    this.paragraphButton = Selector('button.editor-block-list-item-paragraph')
    this.shortcodeLink = Selector('button.editor-block-list-item-shortcode')
    this.shortcodeTextarea = Selector('textarea').withAttribute('placeholder', 'Write shortcode here…')
    this.trashLink = Selector('a').withAttribute('aria-label', 'Move “Test-page” to the Trash')
    this.publishButton = Selector('.editor-post-publish-button__button')
    this.confirmPublishButton = Selector('.editor-post-publish-panel__header-publish-button button').withText('Publish')
    this.updateButton = this.publishButton
  }

  async navigatePage () {
    await t
      .setNativeDialogHandler(() => true)
      .navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
  }

  async addNewPage () {
    await t.click(link('.wrap', 'Add New'))

    if (await this.closePopupButton.exists) await t.click(this.closePopupButton)
    if (await this.closePopupPattern.exists) await t.click(this.closePopupPattern)

    await t
      .typeText(this.titleField, 'Test-page', { paste: true })
      .click(this.addBlockIcon)
      .typeText(this.searchBlock.filterVisible(), 'paragraph', { paste: true })
      .click(this.paragraphButton)
      .typeText(Selector('.is-root-container p:last-of-type'), 'Content', { paste: true })
      .click(this.publishButton)
      .click(this.confirmPublishButton)
  }

  async deleteTestPage () {
    await t
      .navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
      .hover(link('#the-list', 'Test-page'))
      .click(this.trashLink)
  }
}

export default Page
