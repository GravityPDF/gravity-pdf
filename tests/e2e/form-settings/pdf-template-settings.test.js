import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, radioItem, button } from '../page-model/helpers/field'
import Pdf from '../page-model/helpers/pdf'
import FormSettings from '../page-model/form-settings/form-settings'

const pdf = new Pdf()
const run = new FormSettings()

fixture`PDF Template - Template Settings Test`

test('should display Field Border Color field', async t => {
  // Get selectors
  const showPopupPickerBox = Selector('.wp-picker-active')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t.click(button('Select Color').nth(1))

  // Assertions
  await t
    .expect(fieldLabel('Field Border Color').exists).ok()
    .expect(button('Select Color').nth(1).exists).ok()
    .expect(fieldDescription('Control the color of the field border.').exists).ok()
    .expect(showPopupPickerBox.exists).ok()
})

test('should display Show Form Title field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()

  // Assertions
  await t
    .expect(fieldLabel('Show Form Title').exists).ok()
    .expect(radioItem('gfpdf_settings', 'show_form_title', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'show_form_title', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Display the form title at the beginning of the PDF.').exists).ok()
})

test('should display Show Page Names field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()

  // Assertions
  await t
    .expect(fieldLabel('Show Page Names').exists).ok()
    .expect(radioItem('gfpdf_settings', 'show_page_names', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'show_page_names', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Display form page names on the PDF. Requires the use of the Page Break field.').exists).ok()
})

test('should display Show HTML Fields field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()

  // Assertions
  await t
    .expect(fieldLabel('Show HTML Fields').exists).ok()
    .expect(radioItem('gfpdf_settings', 'show_html', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'show_html', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Display HTML fields in the PDF.').exists).ok()
})

test('should display Show Section Break Description field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()

  // Assertions
  await t
    .expect(fieldLabel('Show Section Break Description').exists).ok()
    .expect(radioItem('gfpdf_settings', 'show_section_content', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'show_section_content', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Display the Section Break field description in the PDF.').exists).ok()
})

test('should display Enable Conditional Logic field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()

  // Assertions
  await t
    .expect(fieldLabel('Enable Conditional Logic').exists).ok()
    .expect(radioItem('gfpdf_settings', 'enable_conditional', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'enable_conditional', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('When enabled the PDF will adhere to the form field conditional logic.').exists).ok()
})

test('should display Show Empty Fields field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()

  // Assertions
  await t
    .expect(fieldLabel('Show Empty Fields').exists).ok()
    .expect(radioItem('gfpdf_settings', 'show_empty', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'show_empty', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Display Empty fields in the PDF.').exists).ok()
})

test('should display Background Color field', async t => {
  // Get selectors
  const showBackgroundPickerBox = Selector('.wp-picker-active')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t.click(button('Select Color').nth(2))

  // Assertions
  await t
    .expect(fieldLabel('Background Color').exists).ok()
    .expect(button('Select Color').nth(2).exists).ok()
    .expect(fieldDescription('Set the background color for all pages.').exists).ok()
    .expect(showBackgroundPickerBox.exists).ok()
})

test('should display Background Image field', async t => {
  // Get selectors
  const uploadFileButton = Selector('div').find('[class^="gfpdf_settings_upload_button button-secondary"]')
  const popupMediaBox = Selector('#__wp-uploader-id-0').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t.click(uploadFileButton)

  // Assertions
  await t
    .expect(fieldLabel('Background Image').exists).ok()
    .expect(uploadFileButton.exists).ok()
    .expect(fieldDescription('The background image is included on all pages. For optimal results, use an image the same dimensions as the paper size.').exists).ok()
    .expect(popupMediaBox.count).eql(1)
})

test('should display Header field', async t => {
  // Get selectors
  let wsiwigEditor = Selector('#wp-gfpdf_settings_header-editor-container')
  let showMediabox = Selector('#__wp-uploader-id-0').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t.click(button('Add Media').nth(0))

  // Assertions
  await t
    .expect(fieldLabel('Header').exists).ok()
    .expect(button('Add Media').nth(0).exists).ok()
    .expect(wsiwigEditor.exists).ok()
    .expect(fieldDescription('The header is included at the top of each page. For simple columns try this HTML table snippet.').exists).ok()
    .expect(showMediabox.count).eql(1)
})

test('should display First Page Header field', async t => {
  // Get selectors
  let toggleCheckbox = Selector('label').withText('Use different header on first page of PDF').find('.gfpdf-input-toggle')
  let wsiwigEditor = Selector('#wp-gfpdf_settings_first_header-editor-container').filterVisible()
  let showMediabox = Selector('#__wp-uploader-id-0').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t
    .click(toggleCheckbox)
    .click(button('Add Media').nth(1))

  // Assertions
  await t
    .expect(fieldLabel('First Page Header').exists).ok()
    .expect(toggleCheckbox.exists).ok()
    .expect(fieldDescription('Use different header on first page of PDF?', 'label').exists).ok()
    .expect(button('Add Media').nth(1).exists).ok()
    .expect(wsiwigEditor.count).eql(1)
    .expect(showMediabox.count).eql(1)
})

test('should display Footer field', async t => {
  // Get selectors
  let showMediabox = Selector('#__wp-uploader-id-0').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t.click(button('Add Media').nth(2))

  // Assertions
  await t
    .expect(fieldLabel('Footer').exists).ok()
    .expect(button('Add Media').nth(2).exists).ok()
    .expect(fieldDescription('The footer is included at the bottom of every page. For simple columns try this HTML table snippet.').exists).ok()
    .expect(showMediabox.count).eql(1)
})

test('should display First Page Footer field', async t => {
  // Get selectors
  let toggleCheckbox = Selector('label').withText('Use different footer on first page of PDF').find('.gfpdf-input-toggle')
  let wsiwigEditor = Selector('#wp-gfpdf_settings_first_footer-editor-container').filterVisible()
  let showMediabox = Selector('#__wp-uploader-id-0').filterVisible()

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateTemplateLink()
  await t
    .click(toggleCheckbox)
    .click(button('Add Media').nth(3))

  // Assertions
  await t
    .expect(fieldLabel('First Page Footer').exists).ok()
    .expect(toggleCheckbox.exists).ok()
    .expect(fieldDescription('Use different footer on first page of PDF?', 'label').exists).ok()
    .expect(button('Add Media').nth(3).exists).ok()
    .expect(wsiwigEditor.count).eql(1)
    .expect(showMediabox.count).eql(1)
})
