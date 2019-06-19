import { Selector } from 'testcafe'
import { radioItem, defaultValue } from '../../page-model/helpers/field'
import Pdf from '../../page-model/helpers/pdf'
import General from '../../page-model/global-settings/general/general'

const pdf = new Pdf()
const run = new General()

fixture`General Tab - Check Added PDF To Form For Default Global Settings Test`

test('should check that a new added PDF has the default global settings set', async t => {
  // Get Selectors
  const defaultFontSize = Selector('#gfpdf_settings\\[font_size\\]').withAttribute('value', '10')
  const defaultFontColor = Selector('button').withAttribute('style', 'background-color: rgb(0, 0, 0);')
  const defaultRTL = radioItem('gfpdf_settings', 'rtl', 'No').withAttribute('checked', 'checked')

  // Actions
  await pdf.navigateAddPdf('gf_edit_forms&view=settings&subview=pdf&id=2')
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=2')
  await t
    .hover(run.templateList)
    .click(run.editLink)
    .wait(1000)
    .click(run.appearanceLink)

  // Assertions
  await t
    .expect(defaultValue('Zadani').exists).ok()
    .expect(defaultValue('A4 (210 x 297mm)').exists).ok()
    .expect(defaultValue('Portrait').exists).ok()
    .expect(defaultValue('Dejavu Sans Condensed').exists).ok()
    .expect(defaultFontSize.exists).ok()
    .expect(defaultFontColor.exists).ok()
    .expect(defaultRTL.exists).ok()
})

test('reset/clean PDF templates from the list for the next test', async t => {
  // Actions
  await pdf.navigateDeletePdfEntries('gf_edit_forms&view=settings&subview=pdf&id=2')

  // Assertions
  await t.expect(pdf.template.count).eql(0)
})
