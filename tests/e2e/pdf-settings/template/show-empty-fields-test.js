import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Show empty fields field test`

test('should display \'Show Empty Fields\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Show Section Break Description').exists).ok()
    .expect(run.showEmptyFieldsCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.showEmptyFieldsCheckbox)
    .click(run.saveSettings)
    .expect(run.showEmptyFieldsCheckbox.checked).ok()
    .click(run.showEmptyFieldsCheckbox)
    .click(run.saveSettings)
    .expect(run.showEmptyFieldsCheckbox.checked).notOk()
})
