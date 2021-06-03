import { infoText, button, link } from '../page-model/helpers/field'
import ConfirmationShortcodes from '../page-model/advanced-checks/confirmation-shortcode'
import MergeTags from '../page-model/advanced-checks/merge-tags'

const cs = new ConfirmationShortcodes()
const run = new MergeTags()

fixture`Form Merge Tags Test`

test('should check if form merge tags is working properly', async t => {
  // Actions
  await cs.navigateConfirmationSection('gf_edit_forms&view=settings&subview=confirmation&id=4')
  await t
    .click(cs.confirmationTextCheckbox)
    .click(cs.wysiwgEditorTextTab)
    .click(cs.wysiwgEditor)
    .pressKey('ctrl+a')
    .pressKey('backspace')
  await run.pickMergeTag('Text')
  await run.pickMergeTag('Name (First)')
  await run.pickMergeTag('Name (Last)')
  await run.pickMergeTag('Email')
  await t
    .click(cs.saveConfirmationButton)
    .click(cs.previewLink)
    .typeText(run.textInputField, 'texttest', { paste: true })
    .typeText(run.fNameInputField, 'firstnametest', { paste: true })
    .typeText(run.lNameInputField, 'lastnametest', { paste: true })
    .typeText(run.emailInputField, 'email@test.com', { paste: true })
    .click(cs.submitButton)

  // Assertions
  await t
    .expect(infoText('texttest', 'div').exists).ok()
    .expect(infoText('firstnametest', 'div').exists).ok()
    .expect(infoText('lastnametest', 'div').exists).ok()
    .expect(infoText('email@test.com', 'div').exists).ok()
})
