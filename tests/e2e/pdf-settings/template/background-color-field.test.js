import { Selector } from 'testcafe'
import { fieldLabel } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Background color field test`

test('should display \'Background Color\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.backgroundColorSelectButton)

  // Assertions
  await t
    .expect(fieldLabel('Background Color').exists).ok()
    .expect(run.backgroundColorSelectButton.exists).ok()
    .expect(run.backgroundColorInputBox.exists).ok()
    .expect(run.backgroundColorWpPickerContainerActive.exists).ok()
    .expect(run.backgroundColorWpColorPickerBox.exists).ok()
})

test('should save selected background color', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.backgroundColorSelectButton)
    .click(Selector('#gfpdf-settings-field-wrapper-background_color').find('a').withAttribute('class', 'iris-palette').nth(6))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.backgroundColorInputBox.value).eql('#1e73be')
})
