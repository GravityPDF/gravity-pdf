import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, selectBox, listItem, button, link } from '../page-model/helpers/field'
import Pdf from '../page-model/helpers/pdf'
import FormSettings from '../page-model/form-settings/form-settings'

const pdf = new Pdf()
const run = new FormSettings()

fixture`PDF Template - General Settings Test`

test('should display Name field', async t => {
  // Get Selectors
  const nameInputField = Selector('#gfpdf_settings\\[name\\]')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t.click(link('#tab_PDF', 'Add New'))

  // Assertions
  await t
    .expect(fieldLabel('Name').exists).ok()
    .expect(nameInputField.exists).ok()
})

test('should display Template field', async t => {
  // Get Selectors
  const templatePopupBox = Selector('div').find('[class^="container theme-wrap"]')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(link('#tab_PDF', 'Add New'))
    .click(button('Advanced'))

  // Assertions
  await t
    .expect(fieldLabel('Template').exists).ok()
    .expect(selectBox('chosen-container chosen-container-single chosen-container-single-nosearch', 'gfpdf_settings_template__chosen').exists).ok()
    .expect(button('Advanced').exists).ok()
    .expect(fieldDescription('Choose an existing template or purchased more from our template shop. You can also build your own or hire us to create a custom solution.').exists).ok()
    .expect(templatePopupBox.exists).ok()
})

test('should display Notifications field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t.click(link('#tab_PDF', 'Add New'))

  // Assertions
  await t
    .expect(fieldLabel('Notifications').exists).ok()
    .expect(selectBox('chosen-container chosen-container-multi', 'gfpdf_settings_notification__chosen').exists).ok()
    .expect(fieldDescription('Automatically attach PDF to the selected notifications.').exists).ok()
})

test('should display Filename field', async t => {
  // Get Selectors
  const fileNameInputField = Selector('#gfpdf_settings\\[filename\\]')
  const mergeTagBox = Selector('.open-list.tooltip-merge-tag[title^="<h6>Merge Tags</h6>Merge tags allow you to dynamic"]')
  const mergeTagOptionList = Selector('#gf_merge_tag_list').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(link('#tab_PDF', 'Add New'))
    .click(mergeTagBox)

  // Assertions
  await t
    .expect(fieldLabel('Filename').exists).ok()
    .expect(fileNameInputField.exists).ok()
    .expect(fieldDescription('The name used when saving a PDF. Mergetags are allowed.').exists).ok()
    .expect(mergeTagBox.exists).ok()
    .expect(mergeTagOptionList.count).eql(1)
    .expect(listItem('User IP Address').exists).ok()
    .expect(listItem('Date (mm/dd/yyyy)').exists).ok()
})

test('should display Conditional Logic field', async t => {
  // Get Selectors
  const conditionalLogicField = Selector('#gfpdf_conditional_logic_container').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(link('#tab_PDF', 'Add New'))
    .click(run.conditionalCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('Conditional Logic').exists).ok()
    .expect(run.conditionalCheckbox.exists).ok()
    .expect(fieldDescription('Enable conditional logic', 'label').exists).ok()
    .expect(conditionalLogicField.count).eql(1)
})

test('should toggle additional Conditional Logic field', async t => {
  // Get Selectors
  const conditionalLogicField = Selector('#gfpdf_conditional_logic_container').filterHidden()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(link('#tab_PDF', 'Add New'))
    .click(run.conditionalCheckbox)
    .click(run.conditionalCheckbox)

  // Assertions
  await t.expect(conditionalLogicField.exists).ok()
})

test('should verify that an error is thrown if Name or Filename is empty when trying to add a PDF', async t => {
  // Get Selectors
  const addPdfButton = Selector('div').find('[class^="button-primary"][value="Add PDF"]')
  const nameError = Selector('div').find('[class^=" gfield_error"]').withText('Name *')
  const fileNameError = Selector('div').find('[class^=" gfield_error"]').withText('Filename *')
  const errorMessage = Selector('div').find('[class^="error  notice"]').withText('PDF could not be saved. Please enter all required information below.')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t
    .click(link('#tab_PDF', 'Add New'))
    .click(addPdfButton)

  // Assertions
  await t
    .expect(nameError.exists).ok()
    .expect(fileNameError.exists).ok()
    .expect(errorMessage.exists).ok()
})
