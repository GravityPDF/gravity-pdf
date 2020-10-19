import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Restrict owner field test`

test('should display \'Restrict Owner\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Restrict Owner').exists).ok()
    .expect(fieldDescription('When enabled, the original entry owner will NOT be able to view the PDFs. This setting is overridden when using signed PDF urls.').exists).ok()
    .expect(run.restrictOwnerCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.restrictOwnerCheckbox)
    .click(run.saveSettings)
    .expect(run.restrictOwnerCheckbox.checked).ok()
    .click(run.restrictOwnerCheckbox)
    .click(run.saveSettings)
    .expect(run.restrictOwnerCheckbox.checked).notOk()
})
