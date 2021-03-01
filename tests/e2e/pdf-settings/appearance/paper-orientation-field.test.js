import { fieldLabel, dropdownOption } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF appearance settings - Paper orientation field test`

test('should display \'Paper Orientation\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')

  // Assertions
  await t
    .expect(fieldLabel('Paper Orientation').exists).ok()
    .expect(run.paperOrientationSelectBox.exists).ok()
})

test('should save selected paper orientation value', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.appearanceCollapsiblePanel)
    .click(run.paperOrientationSelectBox)
    .click(dropdownOption('Landscape'))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.paperOrientationSelectBox.value).eql('landscape')
})
