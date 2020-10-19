import { fieldLabel, fieldDescription, dropdownOption } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF template settings - Container background color field test`

test('should display \'Container Background Color\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.templateSelectBox)
    .click(dropdownOption('Rubix'))
    .click(run.saveSettings)
    .click(run.templateCollapsiblePanel)
    .click(run.rubixContainerBackgroundColorSelectButton)

  // Assertions
  await t
    .expect(fieldLabel('Container Background Color').exists).ok()
    .expect(fieldDescription('Control the color of the field background.', 'label').exists).ok()
    .expect(run.rubixContainerBackgroundColorSelectButton.exists).ok()
    .expect(run.rubixContainerBackgroundColorInputBox.exists).ok()
    .expect(run.rubixContainerBackgroundColorWpPickerContainerActive.exists).ok()
    .expect(run.rubixContainerBackgroundColorWpColorPickerBox.exists).ok()
})

test('should save selected container background color', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.templateCollapsiblePanel)
    .click(run.rubixContainerBackgroundColorSelectButton)
    .click(run.rubixContainerBackgroundColorPicker.nth(7))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.rubixContainerBackgroundColorInputBox.value).eql('#8224e3')
})

test('should check that \'Container Background Color\' field doesn\'t exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=4')
  await t
    .click(run.templateSelectBox)
    .click(dropdownOption('Blank Slate'))
    .click(run.saveSettings)
    .click(run.templateCollapsiblePanel)

  // Assertions
  await t
    .expect(fieldLabel('Container Background Color').exists).notOk()
    .expect(fieldDescription('Control the color of the field background.', 'label').exists).notOk()
    .expect(run.rubixContainerBackgroundColorSelectButton.exists).notOk()
    .expect(run.rubixContainerBackgroundColorInputBox.exists).notOk()
    .expect(run.rubixContainerBackgroundColorWpPickerContainerActive.exists).notOk()
    .expect(run.rubixContainerBackgroundColorWpColorPickerBox.exists).notOk()
})
