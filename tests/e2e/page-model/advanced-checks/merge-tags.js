import { Selector, t } from 'testcafe'
import { listItem } from '../helpers/field'

class MergeTags {
  constructor () {
    this.mergeTagsButton = Selector('#gform_setting_message').find('[class^="open-list tooltip-merge-tag"]')
    this.textInputField = Selector('input').withAttribute('name', 'input_1')
    this.fNameInputField = Selector('input').withAttribute('aria-label', 'First name')
    this.lNameInputField = Selector('input').withAttribute('aria-label', 'Last name')
    this.emailInputField = Selector('input').withAttribute('name', 'input_3')
  }

  async pickMergeTag (text) {
    await t
      .click(this.mergeTagsButton)
      .click(listItem(text))
      .pressKey('enter')
  }
}

export default MergeTags
