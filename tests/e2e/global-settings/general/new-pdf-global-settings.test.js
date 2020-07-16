import { Selector } from 'testcafe'
import { listItem, button, radioItem, defaultValue } from '../../page-model/helpers/field'
import Pdf from '../../page-model/helpers/pdf'
import General from '../../page-model/global-settings/general/general'

const run = new General()
const pdf = new Pdf()

fixture`General Tab - Check Added PDF To Form For Updated Global Settings Test`

test('should check that a new added PDF has the updated global settings set', async t => {
  // Get Selectors
  const newPaperSize = listItem('Legal (8.5 x 14in)')
  const testTemplate = '../../resources/test-template.zip'
  const newFont = listItem('Free Sans')
  const newFontColorGreen = Selector('.iris-palette').nth(5)
  const updatedFontColorGreen = Selector('button').withAttribute('style', 'background-color: rgb(129, 215, 66);')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.paperSizeField)
    .click(newPaperSize)
    .click(button('Advanced'))
    .setFilesToUpload(run.addNewTemplate, testTemplate)
    .wait(1000)
    .click(run.testTemplateDetailsLink)
    .click(run.templateSelectButton)
    .click(run.fontField)
    .click(newFont)
    .typeText(run.fontSize, '12', { replace: true })
    .click(button('Select Color'))
    .click(newFontColorGreen)
    .click(radioItem('gfpdf_settings', 'default_rtl', 'Yes'))
    .click(run.saveButton)
  await pdf.navigateAddPdf('gf_edit_forms&view=settings&subview=pdf&id=2')
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=2')
  await t
    .hover(run.templateList)
    .click(run.editLink)
    .click(run.generalLink)
    .expect(defaultValue('Test Template').exists).ok()
    .wait(1000)
    .click(run.appearanceLink)
    .expect(defaultValue('Legal (8.5 x 14in)').exists).ok()
    .expect(defaultValue('Portrait').exists).ok()
    .expect(defaultValue('Free Sans').exists).ok()
    .expect(Selector('#gfpdf_settings\\[font_size\\]').withAttribute('value', '12').exists).ok()
    .expect(updatedFontColorGreen.exists).ok()
    .expect(radioItem('gfpdf_settings', 'rtl', 'Yes').withAttribute('checked', 'checked').exists).ok()
})

test('should reset the new set global settings back to the default global settings', async t => {
  // Get Selectors
  const deleteButton = Selector('a').withText('Delete').nth(0)
  const defaultFontColor = Selector('button').withAttribute('style', 'background-color: rgb(0, 0, 0);')
  const fontColorDefaultButton = Selector('.button.button-small.wp-picker-default')

  // Actions & Assertions
  await pdf.navigateDeletePdfEntries('gf_edit_forms&view=settings&subview=pdf&id=2')
  await t.expect(pdf.template.count).eql(0)
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.paperSizeField)
    .click(listItem('A4 (210 x 297mm)'))
    .setNativeDialogHandler(() => true)
    .click(button('Advanced'))
    .click(run.zadaniDetailsLink)
    .click(run.templateSelectButton)
    .click(button('Advanced'))
    .click(run.testTemplateDetailsLink)
    .click(deleteButton)
    .click(button('Close dialog'))
    .click(run.fontField)
    .click(listItem('Dejavu Sans Condensed'))
    .typeText(run.fontSize, '10', { replace: true })
    .click(button('Select Color'))
    .click(fontColorDefaultButton)
    .click(radioItem('gfpdf_settings', 'default_rtl', 'No'))
    .click(run.saveButton)
    .expect(defaultValue('A4 (210 x 297mm)').exists).ok()
    .expect(defaultValue('Zadani').exists).ok()
    .expect(defaultValue('Dejavu Sans Condensed').exists).ok()
    .expect(Selector('#gfpdf_settings\\[default_font_size\\]').withAttribute('value', '10').exists).ok()
    .expect(defaultFontColor.exists).ok()
    .expect(radioItem('gfpdf_settings', 'default_rtl', 'No').withAttribute('checked', 'checked').exists).ok()
})
