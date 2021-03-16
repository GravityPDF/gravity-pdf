import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Show HTML fields field test`

test('should display \'Show HTML Fields\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Show HTML Fields').exists).ok()
    .expect(fieldDescription('Display form page names on the PDF. Requires the use of the Page Break field.').exists).ok()
    .expect(run.showHtmlFieldsCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.showHtmlFieldsCheckbox)
    .click(run.saveSettings)
    .expect(run.showHtmlFieldsCheckbox.checked).ok()
    .click(run.showHtmlFieldsCheckbox)
    .click(run.saveSettings)
    .expect(run.showHtmlFieldsCheckbox.checked).notOk()
})
