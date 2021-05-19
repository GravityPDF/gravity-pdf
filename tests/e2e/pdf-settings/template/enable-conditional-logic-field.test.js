import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Enable conditional logic field test`

test('should display \'Enable Conditional Logic\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Show Section Break Description').exists).ok()
    .expect(run.enableConditionalLogicCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.enableConditionalLogicCheckbox)
    .click(run.saveSettings)
    .expect(run.enableConditionalLogicCheckbox.checked).notOk()
    .click(run.enableConditionalLogicCheckbox)
    .click(run.saveSettings)
    .expect(run.enableConditionalLogicCheckbox.checked).ok()
})
