import { Selector } from 'testcafe'
import {
  fieldLabel,
  fieldDescription,
  dropdownBox,
  dropdownOption,
  dropdownOptionGroup,
  listItem,
  infoText,
  radioItem,
  button,
  link
} from '../page-model/helpers/field'
import Pdf from '../page-model/helpers/pdf'
import FormSettings from '../page-model/form-settings/form-settings'

const pdf = new Pdf()
const run = new FormSettings()

fixture`PDF Template - Appearance Settings Test`

test('should display Paper Size field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()

  // Assertions
  await t
    .expect(fieldLabel('Paper Size').exists).ok()
    .expect(fieldDescription('Set the paper size used when generating PDFs.').exists).ok()
})

test('should display a dropdown of Paper Sizes option', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()

  // Assertions
  await t
    .expect(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_pdf_size__chosen').filterVisible().count).eql(1)

    .expect(dropdownOptionGroup('Common Sizes').exists).ok()
    .expect(dropdownOption('A4 (210 x 297mm)').exists).ok()
    .expect(dropdownOption('Letter (8.5 x 11in)').exists).ok()

    .expect(dropdownOptionGroup('"A" Sizes').exists).ok()
    .expect(dropdownOption('A0 (841 x 1189mm)').exists).ok()
    .expect(dropdownOption('A1 (594 x 841mm)').exists).ok()

    .expect(dropdownOptionGroup('"B" Sizes').exists).ok()
    .expect(dropdownOption('B0 (1414 x 1000mm)').exists).ok()
    .expect(dropdownOption('B1 (1000 x 707mm)').exists).ok()

    .expect(dropdownOptionGroup('"C" Sizes').exists).ok()
    .expect(dropdownOption('C0 (1297 x 917mm)').exists).ok()
    .expect(dropdownOption('C1 (917 x 648mm)').exists).ok()

    .expect(dropdownOptionGroup('"RA" and "SRA" Sizes').exists).ok()
    .expect(dropdownOption('RA0 (860 x 1220mm)').exists).ok()
    .expect(dropdownOption('RA1 (610 x 860mm)').exists).ok()
})

test('should search and display existing Paper Size result', async t => {
  // Get selectors
  const searchBox = Selector('#gfpdf_settings_pdf_size__chosen').find('.chosen-search-input')
  const result = Selector('div').find('[class^="active-result group-option highlighted"]')

  // Actions & Assertions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()
  await t
    .click(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_pdf_size__chosen'))
    .typeText(searchBox, 'letter', { paste: true })
    .expect(result.count).eql(1)
})

test('should display Custom Paper Size field when selected from Paper Size option', async t => {
  // Get selectors
  const widthInputField = Selector('#gfpdf_settings\\[custom_pdf_size\\]_width')
  const heightInputField = Selector('#gfpdf_settings\\[custom_pdf_size\\]_height')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()
  await t
    .click(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_pdf_size__chosen'))
    .click(listItem('Custom Paper Size'))
    .click(dropdownBox('chosen-container chosen-container-single chosen-container-single-nosearch', 'gfpdf_settings_custom_pdf_size__measurement_chosen'))

  // Assertions
  await t
    .expect(fieldLabel('Custom Paper Size').exists).ok()
    .expect(widthInputField.exists).ok()
    .expect(heightInputField.exists).ok()
    .expect(infoText('Width  Height').exists).ok()
    .expect(dropdownBox('chosen-container chosen-container-single chosen-container-single-nosearch', 'gfpdf_settings_custom_pdf_size__measurement_chosen').filterVisible().count).eql(1)
    .expect(dropdownOption('mm').exists).ok()
    .expect(dropdownOption('inches').exists).ok()
    .expect(fieldDescription('Control the exact paper size. Can be set in millimeters or inches.').exists).ok()
})

test('should display Orientation field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()
  await t.click(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_orientation__chosen'))

  // Assertions
  await t
    .expect(fieldLabel('Orientation').exists).ok()
    .expect(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_orientation__chosen').filterVisible().count).eql(1)
    .expect(listItem('Portrait').exists).ok()
    .expect(listItem('Landscape').exists).ok()
})

test('should display Font field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await t.click(link('#tab_pdf', 'Add New'))

  // Assertions
  await t
    .expect(fieldLabel('Font').exists).ok()
    .expect(fieldDescription('Set the font type used in PDFs. Choose an existing font or install your own.').exists).ok()
})

test('should display a dropdown of Fonts', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()

  // Assertions
  await t
    .expect(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_font__chosen').filterVisible().count).eql(1)

    .expect(dropdownOptionGroup('Unicode').exists).ok()
    .expect(dropdownOption('Dejavu Sans Condensed').exists).ok()
    .expect(dropdownOption('Dejavu Sans').exists).ok()

    .expect(dropdownOptionGroup('Indic').exists).ok()
    .expect(dropdownOption('Lohit Kannada').exists).ok()
    .expect(dropdownOption('Pothana2000').exists).ok()

    .expect(dropdownOptionGroup('Arabic').exists).ok()
    .expect(dropdownOption('XB Riyaz').exists).ok()
    .expect(dropdownOption('Lateef').exists).ok()

    .expect(dropdownOptionGroup('Chinese, Japanese, Korean').exists).ok()
    .expect(dropdownOption('Sun Ext').exists).ok()
    .expect(dropdownOption('Un Batang (Korean)').exists).ok()

    .expect(dropdownOptionGroup('Other').exists).ok()
    .expect(dropdownOption('Estrangelo Edessa (Syriac)').exists).ok()
    .expect(dropdownOption('Kaputa (Sinhala)').exists).ok()
})

test('should search and display existing font result', async t => {
  // Get selectors
  const searchBox = Selector('#gfpdf_settings_font__chosen').find('.chosen-search-input')
  const result = Selector('div').find('[class^="active-result group-option highlighted"]')

  // Actions & Assertions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()
  await t
    .click(dropdownBox('chosen-container chosen-container-single', 'gfpdf_settings_font__chosen'))
    .typeText(searchBox, 'Free Sans', { paste: true })
    .expect(result.count).eql(1)
})

test('should display Font Size field', async t => {
  // Get selectors
  const fontSizeInputBox = Selector('#gfpdf_settings\\[font_size\\]')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()

  // Assertions
  await t
    .expect(fieldLabel('Font Size').exists).ok()
    .expect(fontSizeInputBox.exists).ok()
    .expect(fieldDescription('Set the font size to use in the PDF.').exists).ok()
})

test('should display Font Color field', async t => {
  // Get selectors
  const popupPickerBox = Selector('.wp-picker-container')
  const showPopupPickerBox = Selector('.wp-picker-active')

  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()
  await t.click(button('Select Color'))

  // Assertions
  await t
    .expect(fieldLabel('Font Color').exists).ok()
    .expect(button('Select Color').exists).ok()
    .expect(fieldDescription('Set the font color to use in the PDF.').exists).ok()
    .expect(popupPickerBox.exists).ok()
    .expect(showPopupPickerBox.exists).ok()
})

test('should display Reverse Text (RTL) field', async t => {
  // Actions
  await pdf.navigatePdfSection('gf_edit_forms&view=settings&subview=pdf&id=1')
  await run.navigateAppearanceLink()

  // Assertions
  await t
    .expect(fieldLabel('Reverse Text (RTL)').exists).ok()
    .expect(fieldDescription('Script like Arabic and Hebrew are written right to left.').exists).ok()
    .expect(radioItem('gfpdf_settings', 'rtl', 'Yes').filterVisible().count).eql(1)
    .expect(radioItem('gfpdf_settings', 'rtl', 'No').filterVisible().count).eql(1)
})
