import { Selector, RequestLogger } from 'testcafe'
import { baseURL } from '../auth'
import { button, dropdownOption, link } from '../page-model/helpers/field'
import ConfirmationShortcodes from '../page-model/advanced-checks/confirmation-shortcode'
import Page from '../page-model/helpers/page'

const run = new ConfirmationShortcodes()
const page = new Page()
let shortcodeHolder
let downloadUrl

const downloadLogger = RequestLogger(downloadUrl, {
  logResponseBody: true,
  logResponseHeaders: true
})

fixture`PDF shortcode - Confirmation Type TEXT, PAGE, and REDIRECT Test`

test('should check if the shortcode confirmation type TEXT is working correctly', async t => {
  // Actions
  await run.copyDownloadShortcode('gf_edit_forms&view=settings&subview=pdf&id=3')
  shortcodeHolder = await run.shortcodeInputBox.value
  await run.navigateConfirmationSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(run.confirmationTextCheckbox)
    .click(run.wysiwgEditorTextTab)
    .click(run.wysiwgEditor)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.wysiwgEditor, shortcodeHolder, { paste: true })
    .click(run.saveConfirmationButton)
    .click(run.previewLink)
    .typeText(run.formInputField, 'test', { paste: true })
    .click(run.submitButton)
  downloadUrl = await Selector('.gravitypdf-download-link').getAttribute('href')
  await t
    .click(link('.gform_confirmation_wrapper ', 'Download PDF'))
    .wait(2000)
    .addRequestHooks(downloadLogger)
  await run.responseStatus(downloadLogger._internalRequests, 0)

  // Assertions
  await t
    .expect(run.getStatusCode === 200).ok()
    .expect(run.getContentDisposition === 'attachment; filename="Sample.pdf"').ok()
    .expect(run.getContentType === 'application/pdf').ok()
})

test('should check if the shortcode confirmation type PAGE is working correctly', async t => {
  // Actions
  await run.copyDownloadShortcode('gf_edit_forms&view=settings&subview=pdf&id=3')
  shortcodeHolder = await run.shortcodeInputBox.value
  await t
    .setNativeDialogHandler(() => true)
    .navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
  await page.addNewPage()
  await t
    .setNativeDialogHandler(() => true)
    .navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
    .click(link('#the-list', 'Test page'))

  await page.closePopupButton.exists && await t.click(page.closePopupButton)
  await t
    .click(page.addBlockIcon)
    .typeText(page.searchBlock, 'shortcode', { paste: true })
    .click(page.shortcodeLink)
    .typeText(page.shortcodeTextarea, shortcodeHolder, { paste: true })
    .click(page.updateButton)
  await run.navigateConfirmationSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(run.confirmationPageCheckbox)
    .click(run.confirmationPageSelectBox)
    .click(dropdownOption('Test page'))
  await t
    .click(run.queryStringInputBox)
    .typeText(run.queryStringInputBox, 'entry={entry_id}', { paste: true })
    .click(run.saveConfirmationButton)
    .click(run.previewLink)
    .typeText(run.formInputField, 'test', { paste: true })
    .click(run.submitButton)
  downloadUrl = await Selector('.gravitypdf-download-link').getAttribute('href')
  await t
    .click(link('.entry-content', 'Download PDF'))
    .wait(2000)
    .addRequestHooks(downloadLogger)
  await run.responseStatus(downloadLogger._internalRequests, 0)

  // Assertions
  await t
    .expect(run.getStatusCode === 200).ok()
    .expect(run.getContentDisposition === 'attachment; filename="Sample.pdf"').ok()
    .expect(run.getContentType === 'application/pdf').ok()
})

test('should check if the shortcode confirmation type REDIRECT download is working correctly', async t => {
  // Actions
  await run.copyDownloadShortcode('gf_edit_forms&view=settings&subview=pdf&id=3')
  shortcodeHolder = await run.shortcodeInputBox.value
  await run.navigateConfirmationSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(run.confirmationRedirectCheckbox)
    .click(run.redirectInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.redirectInputBox, shortcodeHolder, { paste: true })
    .click(run.saveConfirmationButton)
    .click(run.previewLink)
  downloadUrl = `${baseURL}/?gf_page=preview&id=3`
  await t
    .typeText(run.formInputField, 'test', { paste: true })
    .click(run.submitButton)
    .wait(2000)
    .addRequestHooks(downloadLogger)
  await run.responseStatus(downloadLogger._internalRequests, 1)

  // Assertions
  await t
    .expect(run.getStatusCode === 200).ok()
    .expect(run.getContentDisposition === 'attachment; filename="Sample.pdf"').ok()
    .expect(run.getContentType === 'application/pdf').ok()
})

test('reset/clean Page entry for the next test', async t => {
  // Actions & Assertions
  await page.navigatePage()
  await t
    .hover(link('#the-list', 'Test page'))
    .click(page.trashLink)
    .expect(link('#the-list', 'Test page').exists).notOk()
})
