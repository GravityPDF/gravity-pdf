import AdvancedCheck from '../utilities/page-model/helpers/advanced-check'
import General from '../utilities/page-model/tabs/general-settings'

let shortcodeHolder
const advancedCheck = new AdvancedCheck()
const general = new General()

fixture`Debug mode - PDF shortcode test`

test('should enable debug mode and throw error when PDF is inactive', async t => {
  // Actions & Assertions
  await advancedCheck.navigateSection('gf_settings&subview=PDF&tab=general#')
  await t
    .click(advancedCheck.debugModeCheckbox)
    .click(general.saveSettings)
    .wait(500)
  await advancedCheck.copyDownloadShortcode('gf_edit_forms&view=settings&subview=PDF&id=3')
  shortcodeHolder = await advancedCheck.shortcodeBox.getAttribute('data-clipboard-text')
  await t.click(advancedCheck.toggleSwitch)
  await advancedCheck.navigateConfirmationSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(advancedCheck.confirmationTextCheckbox)
    .click(advancedCheck.wysiwgEditorTextTab)
    .click(advancedCheck.wysiwgEditor)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(advancedCheck.wysiwgEditor, shortcodeHolder, { paste: true })
    .click(advancedCheck.saveConfirmationButton)

  const url = await advancedCheck.previewLink.href
  await t.navigateTo(url)
    .typeText(advancedCheck.formInputField, 'test', { paste: true })
    .click(advancedCheck.submitButton)
    .expect(advancedCheck.debugModeErrorMessage.exists).ok()
})

test('should reset/clean PDF back to active and debug mode back to disabled for the next test', async t => {
  // Actions & Assertions
  await advancedCheck.navigateSection('gf_edit_forms&view=settings&subview=PDF&id=3')
  await t
    .click(advancedCheck.toggleSwitch)
    .expect(advancedCheck.toggleSwitch.getAttribute('data-status')).eql('active')
  await advancedCheck.navigateLink('gf_settings&subview=PDF&tab=general#')
  await t
    .click(advancedCheck.debugModeCheckbox)
    .click(general.saveSettings)
    .expect(advancedCheck.debugModeCheckbox.checked).notOk()
})
