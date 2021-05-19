import { Selector } from 'testcafe'
import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF general settings - Notifications field test`

test('should display \'Notifications\' field', async t => {
  // Selectors
  const checkbox = Selector('#gfpdf-settings-field-wrapper-notification').find('[class^="gfpdf_settings_notification "]')
  const checkboxLabel = Selector('#gfpdf-settings-field-wrapper-notification').find('label').withText('Admin Notification')

  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Notifications').exists).ok()
    .expect(checkbox.exists).ok()
    .expect(checkboxLabel.exists).ok()
})

test('should save checkbox toggled value', async t => {
  // Selectors
  const checkbox = Selector('#gfpdf-settings-field-wrapper-notification').find('[class^="gfpdf_settings_notification "]')

  // Actions && Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(checkbox)
    .click(run.saveSettings)
    .expect(checkbox.checked).ok()
    .click(checkbox)
    .click(run.saveSettings)
    .expect(checkbox.checked).notOk()
})
