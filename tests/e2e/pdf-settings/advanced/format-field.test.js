import { Selector } from 'testcafe'
import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Format field test`

test('should display \'Format\' field', async t => {
  // Selectors
  const standardLabel = Selector('#gfpdf-settings-field-wrapper-format').find('label').withAttribute('for', 'gfpdf_settings[format][Standard]').withText('Standard')
  const pdfA1bLabel = Selector('#gfpdf-settings-field-wrapper-format').find('label').withAttribute('for', 'gfpdf_settings[format][PDFA1B]').withText('PDF/A-1b')
  const pdfX1aLabel = Selector('#gfpdf-settings-field-wrapper-format').find('label').withAttribute('for', 'gfpdf_settings[format][PDFX1A]').withText('PDF/X-1a')

  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Format').exists).ok()
    .expect(run.formatStandardCheckbox.exists).ok()
    .expect(standardLabel.exists).ok()
    .expect(run.formatPdfA1bCheckbox.exists).ok()
    .expect(pdfA1bLabel.exists).ok()
    .expect(run.formatPdfX1aCheckbox.exists).ok()
    .expect(pdfX1aLabel.exists).ok()
})

test('should display added fields if \'Standard\' option is checked', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)

  // Assertions
  await t
    .expect(run.enablePdfSecurityField.visible).ok()
    .expect(run.passwordField.visible).ok()
    .expect(run.privilegesField.visible).ok()
})

test('should hide \'Standard\' added fields if \'PDF/A-1b\' option is checked', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.formatPdfA1bCheckbox)

  // Assertions
  await t
    .expect(run.enablePdfSecurityField.visible).notOk()
    .expect(run.passwordField.visible).notOk()
    .expect(run.privilegesField.visible).notOk()
})

test('should hide \'Standard\' added fields if \'PDF/X-1a\' option is checked', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.formatPdfX1aCheckbox)

  // Assertions
  await t
    .expect(run.enablePdfSecurityField.visible).notOk()
    .expect(run.passwordField.visible).notOk()
    .expect(run.privilegesField.visible).notOk()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.saveSettings)
    .expect(run.formatStandardCheckbox.checked).ok()
    .click(run.formatPdfA1bCheckbox)
    .click(run.saveSettings)
    .expect(run.formatPdfA1bCheckbox.checked).ok()
    .click(run.formatPdfX1aCheckbox)
    .click(run.saveSettings)
    .expect(run.formatPdfX1aCheckbox.checked).ok()
})
