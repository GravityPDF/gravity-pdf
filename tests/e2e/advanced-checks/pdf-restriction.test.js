import { baseURL } from '../auth'
import ConfirmationShortcodes from '../page-model/advanced-checks/confirmation-shortcode'
import PdfRestriction from '../page-model/advanced-checks/pdf-restriction'

const cs = new ConfirmationShortcodes()
const run = new PdfRestriction()
let pdfId

fixture`PDF Administrator & Non-Administrator - Restriction Test`
  .page(`${baseURL}/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=pdf&id=4`)

test('should throw an error when a non-administrator user try to access a PDF', async t => {
  // Actions & Assertions
  await run.login(t, 'admin')
  await t
    .setNativeDialogHandler(() => true)
    .navigateTo(`${baseURL}/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=pdf&id=4`)
  pdfId = await cs.shortcodeInputBox.value
  pdfId = pdfId.substring(30, 43)
  await t
    .hover(run.wpAdminBar)
    .click(run.logout)
  await run.login(t, 'editor')
  await t
    .setNativeDialogHandler(() => true)
    .navigateTo(`${baseURL}/pdf/${pdfId}/4`)
    .expect(run.errorMessage.exists).ok()
})

test('should throw an error when a logout user try to access a PDF', async t => {
  // Actions & Assertions
  await t
    .setNativeDialogHandler(() => true)
    .navigateTo(`${baseURL}/pdf/${pdfId}/4`)
    .expect(run.errorMessage.exists).ok()
})
