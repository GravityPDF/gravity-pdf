import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Always save PDF field test`

test('should display \'Always Save PDF\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Always Save PDF').exists).ok()
    .expect(fieldDescription('Enable this option to force the PDF to be saved to disk during form submission (by default, this only occurs when PDFs are attached to notifications). This is useful when using the gfpdf_post_pdf_save hook to copy the PDF to an alternate location on the filesystem.').exists).ok()
    .expect(run.alwaysSavePdfCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.alwaysSavePdfCheckbox)
    .click(run.saveSettings)
    .expect(run.alwaysSavePdfCheckbox.checked).ok()
    .click(run.alwaysSavePdfCheckbox)
    .click(run.saveSettings)
    .expect(run.alwaysSavePdfCheckbox.checked).notOk()
})
