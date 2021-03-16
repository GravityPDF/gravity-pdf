import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Show page names field test`

test('should display \'Show Page Names\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Show Page Names').exists).ok()
    .expect(fieldDescription('Display form page names on the PDF. Requires the use of the Page Break field.').exists).ok()
    .expect(run.showPageNamesCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.showPageNamesCheckbox)
    .click(run.saveSettings)
    .expect(run.showPageNamesCheckbox.checked).ok()
    .click(run.showPageNamesCheckbox)
    .click(run.saveSettings)
    .expect(run.showPageNamesCheckbox.checked).notOk()
})
