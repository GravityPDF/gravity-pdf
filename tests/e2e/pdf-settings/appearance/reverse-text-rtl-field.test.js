import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF appearance settings - Reverse text (RTL) field test`

test('should display \'Reverse Text (RTL)\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Reverse Text (RTL)').exists).ok()
    .expect(fieldDescription('Script like Arabic, Hebrew, Syriac (and many others) are written right to left.').exists).ok()
    .expect(run.rtlCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.rtlCheckbox)
    .click(run.saveSettings)
    .expect(run.rtlCheckbox.checked).ok()
    .click(run.rtlCheckbox)
    .click(run.saveSettings)
    .expect(run.rtlCheckbox.checked).notOk()
})
