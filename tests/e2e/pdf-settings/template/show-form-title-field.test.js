import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Show form title field test`

test('should display \'Show Form Title\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Show Form Title').exists).ok()
    .expect(fieldDescription('Display the form title at the beginning of the PDF.').exists).ok()
    .expect(run.showFormTitleCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.showFormTitleCheckbox)
    .click(run.saveSettings)
    .expect(run.showFormTitleCheckbox.checked).ok()
    .click(run.showFormTitleCheckbox)
    .click(run.saveSettings)
    .expect(run.showFormTitleCheckbox.checked).notOk()
})
