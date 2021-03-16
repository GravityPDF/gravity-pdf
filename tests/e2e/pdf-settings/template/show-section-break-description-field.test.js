import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Show section break description field test`

test('should display \'Show Section Break Description\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Show Section Break Description').exists).ok()
    .expect(fieldDescription('Display the Section Break field description in the PDF.').exists).ok()
    .expect(run.showSectionBreakDescriptionCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.showSectionBreakDescriptionCheckbox)
    .click(run.saveSettings)
    .expect(run.showSectionBreakDescriptionCheckbox.checked).ok()
    .click(run.showSectionBreakDescriptionCheckbox)
    .click(run.saveSettings)
    .expect(run.showSectionBreakDescriptionCheckbox.checked).notOk()
})
