import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Enable PDF security field test`

test('should display \'Enable PDF Security\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.formatStandardCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('Enable PDF Security').exists).ok()
    .expect(fieldDescription('Password protect generated PDFs, and/or restrict user capabilities.').exists).ok()
    .expect(run.enablePdfSecurityCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.saveSettings)
    .expect(run.enablePdfSecurityCheckbox.checked).ok()
    .click(run.enablePdfSecurityCheckbox)
    .click(run.saveSettings)
    .expect(run.enablePdfSecurityCheckbox.checked).notOk()
})
