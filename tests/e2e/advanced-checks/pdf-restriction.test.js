import { RequestLogger } from 'testcafe'
import { baseURL } from '../auth'
import AdvancedCheck from '../utilities/page-model/helpers/advanced-check'
import Page from '../utilities/page-model/helpers/page'
import Pdf from '../utilities/page-model/helpers/pdf'

let pdfId
let downloadUrl
const advancedCheck = new AdvancedCheck()
const page = new Page()
const pdf = new Pdf()
const downloadLogger = RequestLogger(downloadUrl, { logResponseBody: true, logResponseHeaders: true })

fixture`PDF Administrator & Non-Administrator - Restriction Test`

test('should throw an error when a non-administrator user try to access a PDF generated by an admin', async t => {
  // Actions & Assertions
  await advancedCheck.navigateSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  pdfId = await advancedCheck.shortcodeBox.getAttribute('data-clipboard-text')
  pdfId = pdfId.substring(30, 43)
  await advancedCheck.WpLogout()
  await advancedCheck.pdfRestrictionLogin('editor')
  await t
    .navigateTo(`${baseURL}/pdf/${pdfId}/4`)
    .expect(advancedCheck.pdfRestrictionErrorMessage.exists).ok()
})

test('should redirect to WP login page if \'Restrict Owner\' is enabled', async t => {
  // Actions & Assertions
  await advancedCheck.toggleRestrictOwnerCheckbox('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
  await page.addNewPage()
  await t.navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
  await t
    .click(page.testPageLink)
    .click(page.addBlockIcon)
    .typeText(page.searchBlock, 'shortcode', { paste: true })
    .click(page.shortcodeLink)
    .typeText(page.shortcodeTextarea, '[gravityform id=4 title=false description=false ajax=true tabindex=49]', { paste: true })
    .click(page.updateButton)
  await t.navigateTo(`${baseURL}/wp-admin/edit.php?post_type=page`)
  await advancedCheck.WpLogout()
  await t.navigateTo(`${baseURL}/test-page/`)
  await t
    .typeText(advancedCheck.textInputField, 'texttest', { paste: true })
    .click(advancedCheck.submitButton)
    .navigateTo(`${baseURL}/wp-admin/admin.php?page=gf_entries&id=4`)
    .typeText('#user_login', 'admin', { paste: true })
    .typeText('#user_pass', 'password', { paste: true })
    .click('#wp-submit')
  const url = await advancedCheck.viewEntryLink.getAttribute('href')
  await t
    .hover(advancedCheck.wpAdminBar)
    .click(advancedCheck.logout)
    .navigateTo(url)
    .expect(advancedCheck.pdfRestrictionErrorMessage.exists).notOk()
    .expect(advancedCheck.wpLoginForm.exists).ok()
})

test('reset/clean previous tests saved data and ensure PDF can be viewed by default', async t => {
  // Actions & Assertions
  await advancedCheck.toggleRestrictOwnerCheckbox('gf_edit_forms&view=settings&subview=PDF&id=4')
  await advancedCheck.WpLogout()
  await t.navigateTo(`${baseURL}/test-page/`)
  await advancedCheck.submitNewPdfEntry()
  await pdf.navigatePdfEntries('gf_entries&id=4')
  downloadUrl = await advancedCheck.viewEntryLink.getAttribute('href')
  await page.deleteTestPage()
  await t
    .hover(advancedCheck.wpAdminBar)
    .click(advancedCheck.logout)

  downloadLogger.clear()
  await t
    .addRequestHooks(downloadLogger)
    .navigateTo(downloadUrl)
    .wait(1000)
    .removeRequestHooks(downloadLogger)

  // Assertions
  await t
    .expect(downloadLogger.contains(r => r.response.statusCode === 200)).ok()
    .expect(downloadLogger.contains(r => r.response.headers['content-type'] === 'application/pdf')).ok()
})
