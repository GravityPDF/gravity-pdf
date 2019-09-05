import { Selector, RequestLogger } from 'testcafe'
import { baseURL } from '../auth'
import { button, dropdownOption, link } from '../page-model/helpers/field'
import ConfirmationShortcodes from '../page-model/advanced-checks/confirmation-shortcode'
import Page from '../page-model/helpers/page'

const run = new ConfirmationShortcodes()
const page = new Page()
let shorcodeHolder
let downloadUrl

const downloadLogger = RequestLogger(downloadUrl, {
  logResponseBody: true,
  logResponseHeaders: true
})

fixture`PDF shortcode - Confirmation Type TEXT, PAGE, and REDIRECT Test`

test('should check if the shortcode confirmation type TEXT is working correctly', async t => {
  // Actions
  await run.copyDownloadShortcode('gf_edit_forms&view=settings&subview=pdf&id=3')
  shorcodeHolder = await run.shortcodeField.value
  await run.navigateConfirmationsSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(run.confirmationText)
    .click(button('Text'))
    .click(run.wsiwigEditor)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.wsiwigEditor, shorcodeHolder, { paste: true })
    .click(run.saveButton)
    .click(link('#gf_form_toolbar', 'Preview'))
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
  shorcodeHolder = await run.shortcodeField.value
  await page.addNewPage()
  await page.navigatePage()
  await t
    .click(link('#the-list', 'Test page'))
  await page.closePopupButton.exists && await t.click(page.closePopupButton)
  await t
    .click(page.addBlockIcon)
    .typeText(page.searchBlock, 'shortcode', { paste: true })
    .click(page.shortcodeLink)
    .typeText(page.shortcodeTextarea, shorcodeHolder, { paste: true })
    .click(button('Update'))
  await run.navigateConfirmationsSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(run.confirmationPage)
    .click(run.pageSelect)
    .click(dropdownOption('Test page'))
  await t
    .click(run.queryStringBox)
    .typeText(run.textAreaBox, 'entry={entry_id}', { paste: true })
    .click(run.saveButton)
    .click(link('#gf_form_toolbar', 'Preview'))
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
  shorcodeHolder = await run.shortcodeField.value
  await run.navigateConfirmationsSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(run.confirmationRedirect)
    .click(run.redirectUrlInputField)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.redirectUrlInputField, shorcodeHolder, { paste: true })
    .click(run.saveButton)
    .click(link('#gf_form_toolbar', 'Preview'))
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
