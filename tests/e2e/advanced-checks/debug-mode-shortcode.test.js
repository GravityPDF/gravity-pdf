import { button, radioItem, link } from '../page-model/helpers/field'
import Pdf from '../page-model/helpers/pdf'
import DebugModeShortcode from '../page-model/advanced-checks/debug-mode-shortcode'
import ConfirmationShortcodes from '../page-model/advanced-checks/confirmation-shortcode'
import PdfTemplateEntries from '../page-model/form-settings/pdf-template-entries'

const pdf = new Pdf()
const run = new DebugModeShortcode()
const cs = new ConfirmationShortcodes()
const pte = new PdfTemplateEntries()
let shorcodeHolder

fixture`Debug Mode - PDF Shortcode Test`

test('should enable debug mode and throw error when PDF is inactive', async t => {
  // Actions & Assertions
  await pdf.navigatePdfSection('gf_settings&subview=pdf&tab=general#')
  await t
    .click(radioItem('gfpdf_settings', 'debug_mode', 'Yes'))
    .click(run.saveButton)
    .wait(1000)
  await cs.copyDownloadShortcode('gf_edit_forms&view=settings&subview=pdf&id=3')
  shorcodeHolder = await cs.shortcodeField.value
  await t.click(pte.toggleSwitch)
  await cs.navigateConfirmationsSection('gf_edit_forms&view=settings&subview=confirmation&id=3')
  await t
    .click(cs.confirmationText)
    .click(button('Text'))
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(cs.wsiwigEditor, shorcodeHolder, { paste: true })
    .click(cs.saveButton)
    .click(link('#gf_form_toolbar', 'Preview'))
    .typeText(cs.formInputField, 'test', { paste: true })
    .click(cs.submitButton)
    .expect(run.errorMessage.exists).ok()
})

test('should reset/clean PDF back to active and debug mode back to disabled for the next test', async t => {
  // Actions & Assertions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=3')
  await t
    .click(pte.toggleSwitch)
    .expect(run.activePDF.exists).ok()
  await run.navigateLink('gf_settings&subview=pdf&tab=general#')
  await t
    .click(radioItem('gfpdf_settings', 'debug_mode', 'No'))
    .click(run.saveButton)
    .wait(1000)
    .expect(run.noSelected.exists).ok()
})
