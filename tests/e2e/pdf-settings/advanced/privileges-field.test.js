import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Privileges field test`

test('should display \'Privileges\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('Privileges').exists).ok()
    .expect(run.copyCheckbox.checked).ok()
    .expect(run.printLowResolutionCheckbox.checked).ok()
    .expect(run.printHighResolutionCheckbox.checked).ok()
    .expect(run.modifyCheckbox.checked).ok()
    .expect(run.annotateCheckbox.checked).ok()
    .expect(run.fillFormsCheckbox.checked).ok()
    .expect(run.extractCheckbox.checked).ok()
    .expect(run.assembleCheckbox.checked).ok()
})

test('should save toggled checkboxes value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.copyCheckbox)
    .click(run.printLowResolutionCheckbox)
    .click(run.printHighResolutionCheckbox)
    .click(run.modifyCheckbox)
    .click(run.annotateCheckbox)
    .click(run.fillFormsCheckbox)
    .click(run.extractCheckbox)
    .click(run.assembleCheckbox)
    .click(run.saveSettings)
    .expect(run.copyCheckbox.checked).notOk()
    .expect(run.printLowResolutionCheckbox.checked).notOk()
    .expect(run.printHighResolutionCheckbox.checked).notOk()
    .expect(run.modifyCheckbox.checked).notOk()
    .expect(run.annotateCheckbox.checked).notOk()
    .expect(run.fillFormsCheckbox.checked).notOk()
    .expect(run.extractCheckbox.checked).notOk()
    .expect(run.assembleCheckbox.checked).notOk()
    .click(run.copyCheckbox)
    .click(run.printLowResolutionCheckbox)
    .click(run.printHighResolutionCheckbox)
    .click(run.modifyCheckbox)
    .click(run.annotateCheckbox)
    .click(run.fillFormsCheckbox)
    .click(run.extractCheckbox)
    .click(run.assembleCheckbox)
    .click(run.saveSettings)
    .expect(run.copyCheckbox.checked).ok()
    .expect(run.printLowResolutionCheckbox.checked).ok()
    .expect(run.printHighResolutionCheckbox.checked).ok()
    .expect(run.modifyCheckbox.checked).ok()
    .expect(run.annotateCheckbox.checked).ok()
    .expect(run.fillFormsCheckbox.checked).ok()
    .expect(run.extractCheckbox.checked).ok()
    .expect(run.assembleCheckbox.checked).ok()
})

test('should disable/reset PDF security field and hide privileges field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.privilegesField.visible).notOk()
})
