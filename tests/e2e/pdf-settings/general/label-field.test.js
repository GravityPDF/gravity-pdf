import { Selector } from 'testcafe'
import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF general settings - Label field test`

test('should display \'Label\' field', async t => {
  // Selectors
  const labelInputField = Selector('#gfpdf_settings\\[name\\]')

  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Label (required)').exists).ok()
    .expect(labelInputField.exists).ok()
})

test('should save new label', async t => {
  // Selectors
  const labelInputField = Selector('#gfpdf_settings\\[name\\]')

  // Actions && Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(labelInputField)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(labelInputField, 'New sample', { paste: true })
    .click(run.saveSettings)
    .expect(labelInputField.value).eql('New sample')
    .click(labelInputField)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(labelInputField, 'Sample', { paste: true })
    .click(run.saveSettings)
    .expect(labelInputField.value).eql('Sample')
})
