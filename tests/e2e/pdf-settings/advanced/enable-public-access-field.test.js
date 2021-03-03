import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Enable public access field test`

test('should display \'Enable Public Access\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Enable Public Access').exists).ok()
    .expect(fieldDescription('When public access is on all security protocols are disabled and anyone can view the PDF document for ALL your form\'s entries. For better security, use the signed PDF urls feature instead.').exists).ok()
    .expect(run.enablePublicAccessCheckbox.exists).ok()
})

test('should save toggled checkbox value', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.enablePublicAccessCheckbox)
    .click(run.saveSettings)
    .expect(run.enablePublicAccessCheckbox.checked).ok()
    .click(run.enablePublicAccessCheckbox)
    .click(run.saveSettings)
    .expect(run.enablePublicAccessCheckbox.checked).notOk()
})

test('should hide \'Restrict Owner\' field if \'Enable Public Access\' checkbox is checked', async t => {
  // Selectors
  const restrictOwnerField = Selector('#gfpdf-settings-field-wrapper-restrict_owner')

  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.enablePublicAccessCheckbox)
    .click(run.saveSettings())
    .expect(restrictOwnerField.visible).notOk()
    .click(run.enablePublicAccessCheckbox)
    .click(run.saveSettings())
    .expect(restrictOwnerField.visible).ok()
})
