import AdvancedCheck from '../utilities/page-model/helpers/advanced-check'
import { baseURL } from '../auth'

const advanceCheck = new AdvancedCheck()

fixture`PDF template test`

test('should successfully add new PDF template into form template list', async t => {
  // Actions & Assertions
  await advanceCheck.navigateAddPdf('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t.expect(advanceCheck.templateItem.count).eql(1)
})

test('should successfully saved toggled switch value for active and inactive template', async t => {
  // Actions & Assertions
  await advanceCheck.navigateSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(advanceCheck.toggleSwitch)
    .expect(advanceCheck.activePdfTemplate.exists).notOk()
    .expect(advanceCheck.inactivePdfTemplate.exists).ok()
    .click(advanceCheck.toggleSwitch)
    .expect(advanceCheck.activePdfTemplate.exists).ok()
    .expect(advanceCheck.inactivePdfTemplate.exists).notOk()
})

test('should check that the option \'View PDF\' link is disabled when template is inactive', async t => {
  // Actions & Assertions
  await advanceCheck.navigateSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(advanceCheck.toggleSwitch)
    .expect(advanceCheck.inactivePdfTemplate.exists).ok()
  await t.navigateTo(`${baseURL}/wp-admin/admin.php?page=gf_entries&id=1`)
  await t
    .hover(advanceCheck.entryItemSection)
    .expect(advanceCheck.viewPdfLink.exists).notOk()
})

test('should successfully switch from inactive template to active', async t => {
  await advanceCheck.navigateSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(advanceCheck.toggleSwitch)
    .expect(advanceCheck.activePdfTemplate.exists).ok()
})

test('should check that the option \'View PDF\' link is enabled when template is active', async t => {
  // Actions & Assertions
  await advanceCheck.navigateSection('gf_entries&id=1')
  await t
    .hover(advanceCheck.entryItemSection)
    .expect(advanceCheck.viewPdfLink.exists).ok()
})

test('should check that the option \'View PDF\' link is disabled, when the PDF is active but the PDF conditional logic is checked', async t => {
  // Actions & Assertions
  await advanceCheck.navigateSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .hover(advanceCheck.templateItem)
    .click(advanceCheck.editLink)
    .click(advanceCheck.conditionalLogicCheckbox)
    .click(advanceCheck.addUpdatePdfButton)
  await advanceCheck.navigateLink('gf_entries&id=1')
  await t
    .hover(advanceCheck.entryItemSection)
    .expect(advanceCheck.viewPdfLink.exists).notOk()
})

test('should successfully edit/update existing template using the edit template link option', async t => {
  // Actions & Assertions
  await advanceCheck.navigateSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .hover(advanceCheck.templateItem)
    .click(advanceCheck.editLink)
    .click(advanceCheck.pdfLabelNameInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(advanceCheck.pdfLabelNameInputBox, 'Test PDF Template', { paste: true })
    .click(advanceCheck.pdfFilenameInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(advanceCheck.pdfFilenameInputBox, 'testpdftemplate', { paste: true })
    .click(advanceCheck.addUpdatePdfButton)
    .wait(500)
    .click(advanceCheck.pdfListSection)
    .expect(advanceCheck.templateDetail.innerText).contains('Test PDF Template')
})

test('should successfully duplicate existing PDF template using the duplicate link option', async t => {
  // Actions & Assertions
  await advanceCheck.navigateSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .hover(advanceCheck.templateItem)
    .click(advanceCheck.duplicateLink)
  await advanceCheck.navigateLink('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t.expect(advanceCheck.templateItem.count).eql(2)
})

test('reset/clean PDF templates from the list for the next test', async t => {
  // Actions
  await advanceCheck.navigateDeletePdfEntries('gf_edit_forms&view=settings&subview=pdf&id=1')

  // Assertions
  await t.expect(advanceCheck.templateItem.count).eql(0)
})
