import { infoText } from '../utilities/page-model/helpers/field'
import AdvancedCheck from '../utilities/page-model/helpers/advanced-check'

const advancedCheck = new AdvancedCheck()

fixture`Form merge tags test`

test('should check if form merge tags is working properly', async t => {
  // Actions
  await advancedCheck.navigateConfirmationSection('gf_edit_forms&view=settings&subview=confirmation&id=4')
  await t
    .click(advancedCheck.confirmationTextCheckbox)
    .click(advancedCheck.wsiwigEditor)
    .pressKey('ctrl+a')
    .pressKey('backspace')
  await advancedCheck.pickMergeTag('Text')
  await advancedCheck.pickMergeTag('Name (First)')
  await advancedCheck.pickMergeTag('Name (Last)')
  await advancedCheck.pickMergeTag('Email')
  await t
    .click(advancedCheck.saveConfirmationButton)
    .click(advancedCheck.previewLink)
    .typeText(advancedCheck.textInputField, 'texttest', { paste: true })
    .typeText(advancedCheck.fNameInputField, 'firstnametest', { paste: true })
    .typeText(advancedCheck.lNameInputField, 'lastnametest', { paste: true })
    .typeText(advancedCheck.emailInputField, 'email@test.com', { paste: true })
    .click(advancedCheck.submitButton)

  // Assertions
  await t
    .expect(infoText('texttest', 'div').exists).ok()
    .expect(infoText('firstnametest', 'div').exists).ok()
    .expect(infoText('lastnametest', 'div').exists).ok()
    .expect(infoText('email@test.com', 'div').exists).ok()
})
