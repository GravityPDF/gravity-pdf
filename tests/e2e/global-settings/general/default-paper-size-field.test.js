import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, selectBox, dropdownOptionGroup, dropdownOption } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab -  Default Paper Size Field Test`

test('should display \'Default Paper Size\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Paper Size').exists).ok()
    .expect(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_pdf_size__chosen').exists).ok()
    .expect(fieldDescription('Set the default paper size used when generating PDFs.', 'label').exists).ok()
})

test('should search and display existing result', async t => {
  // Get selectors
  const searchBox = Selector('#gfpdf_settings_default_pdf_size__chosen').find('.chosen-search-input')
  const result = Selector('div').find('[class^="active-result group-option highlighted"]')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_pdf_size__chosen'))
    .typeText(searchBox, 'letter', { paste: true })
    .expect(result.count).eql(1)
})

test('should display a dropdown of paper sizes option', async t => {
  // Get selectors
  const dropDownList = Selector('.chosen-results')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(dropDownList.exists).ok()
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
