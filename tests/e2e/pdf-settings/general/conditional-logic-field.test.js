import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF general settings - Conditional logic field test`

test('should display \'Conditional Logic\' field', async t => {
  // Selectors
  const checkboxLabel = Selector('#gfpdf-settings-field-wrapper-conditional').find('label').withText('Enable conditional logic')

  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Conditional Logic').exists).ok()
    .expect(fieldDescription('Add rules to dynamically enable or disable the PDF. When disabled, PDFs do not show up in the admin area, cannot be viewed, and will not be attached to notifications.', 'label').exists).ok()
    .expect(run.conditionalLogicCheckbox.exists).ok()
    .expect(checkboxLabel.exists).ok()
})

test('should save checkbox toggled value', async t => {
  // Actions && Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.conditionalLogicCheckbox)
    .click(run.saveSettings)
    .expect(run.conditionalLogicCheckbox.checked).ok()
    .click(run.conditionalLogicCheckbox)
    .click(run.saveSettings)
    .expect(run.conditionalLogicCheckbox.checked).notOk()
})

test('should display conditional logic settings field if checkbox is checked', async t => {
  // Selectors
  const conditionalLogicSettingsField = Selector('#gfpdf-settings-field-wrapper-conditional').find('[class^="gform-settings-field__conditional-logic"][id="gfpdf_conditional_logic_container"]')

  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.conditionalLogicCheckbox)
    .expect(conditionalLogicSettingsField.visible).ok()
    .click(run.conditionalLogicCheckbox)
    .expect(conditionalLogicSettingsField.visible).notOk()
})
