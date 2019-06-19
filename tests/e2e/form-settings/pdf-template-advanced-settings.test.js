import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, radioItem } from '../page-model/helpers/field'
import Pdf from '../page-model/helpers/pdf'
import FormSettings from '../page-model/form-settings/form-settings'

const pdf = new Pdf()
const run = new FormSettings()

fixture`PDF Template - Advanced Settings Test`

test('should display Format field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()

  // Assertions
  await t
    .expect(fieldLabel('Format').exists).ok()
    .expect(radioItem('gfpdf_settings', 'format', 'Standard').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'format', 'PDFA1B').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'format', 'PDFX1A').filterVisible().count).eql(1)
    .expect(fieldDescription('Generate a PDF in the selected format.').exists).ok()
})

test('should hide Enable PDF Security field when the Format is not "Standard"', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()
  await t.click(radioItem('gfpdf_settings', 'format', 'PDFA1B'))

  // Assertions
  await t
    .expect(fieldLabel('Enable PDF Security').filterHidden().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'security', 'Yes').filterHidden().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'security', 'No').filterHidden().count).eql(1)
    .expect(fieldDescription('Password protect generated PDFs, or restrict user capabilities.').filterHidden().count).eql(1)
})

test('should display Enable PDF Security field', async t => {
  // Get selectors
  const passwordInputField = Selector('#gfpdf_settings\\[password\\]')
  const mergeTagDropdown = Selector('.open-list.tooltip-merge-tag[title^="<h6>Merge Tags</h6>Merge tags allow you to dynamic"]')

  const privilegesBox = Selector('#gfpdf_settings_privileges__chosen')
  const privilegesBoxExtended = Selector('div').find('[class^="chosen-container chosen-container-multi chosen-with-drop chosen-container-active"]')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()
  await t
    .click(radioItem('gfpdf_settings', 'security', 'Yes'))
    .click(privilegesBox)

  // Assertions
  await t
    .expect(fieldLabel('Enable PDF Security').exists).ok()
    .expect(radioItem('gfpdf_settings', 'security', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'security', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Password protect generated PDFs, or restrict user capabilities.').exists).ok()

    .expect(fieldLabel('Password').exists).ok()
    .expect(passwordInputField.exists).ok()
    .expect(mergeTagDropdown.exists).ok()
    .expect(fieldDescription('Password protect the PDF, or leave blank to disable password protection.').exists).ok()

    .expect(fieldLabel('Privileges').exists).ok()
    .expect(fieldDescription('Restrict end user capabilities by removing privileges.').exists).ok()
    .expect(privilegesBox.exists).ok()
    .expect(privilegesBoxExtended.exists).ok()
})

test('should display Image DPI field', async t => {
  // Get selectors
  const fieldInputBox = Selector('#gfpdf_settings\\[image_dpi\\]')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()

  // Assertions
  await t
    .expect(fieldLabel('Image DPI').exists).ok()
    .expect(fieldInputBox.exists).ok()
})

test('should display Always Save PDF field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()

  // Assertions
  await t
    .expect(fieldLabel('Always Save PDF').exists).ok()
    .expect(radioItem('gfpdf_settings', 'save', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'save', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Force a PDF to be saved to disk when a new entry is created.').exists).ok()
})

test('should display Enable Public Access field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()
  await t.click(radioItem('gfpdf_settings', 'public_access', 'Yes'))

  // Assertions
  await t
    .expect(fieldLabel('Enable Public Access').exists).ok()
    .expect(radioItem('gfpdf_settings', 'public_access', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'public_access', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('Disable all security protocols and allow anyone to access the PDFs.').exists).ok()

    .expect(fieldLabel('Restrict Owner').filterHidden().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'restrict_owner', 'Yes').filterHidden().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'restrict_owner', 'No').filterHidden().count).eql(1)
    .expect(fieldDescription('When enabled, the original entry owner will NOT be able to view the PDFs.').filterHidden().count).eql(1)
})

test('should display Restrict Owner field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAdvancedLink()

  // Assertions
  await t
    .expect(fieldLabel('Restrict Owner').exists).ok()
    .expect(radioItem('gfpdf_settings', 'restrict_owner', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'restrict_owner', 'No').filterVisible().count).eql(1)
    .expect(fieldDescription('When enabled, the original entry owner will NOT be able to view the PDFs.').exists).ok()
})
