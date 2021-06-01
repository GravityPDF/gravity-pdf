import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, selectBox, dropdownOptionGroup, dropdownOption } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Default Font Field Test`

test('should display Default Font Field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Font').exists).ok()
    .expect(fieldDescription('Set the default font type used in PDFs. Choose an existing font or install your own.', 'label').exists).ok()
    .expect(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_font__chosen').exists).ok()
})

test('should search and display existing result', async t => {
  // Get selectors
  const searchBox = Selector('#gfpdf_settings_default_font__chosen').find('.chosen-search-input')
  const result = Selector('div').find('[class^="active-result group-option highlighted"]')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_font__chosen'))
    .typeText(searchBox, 'Free Sans', { paste: true })
    .expect(result.count).eql(1)
})

test('should display a dropdown of Default Fonts', async t => {
  // Get selectors
  const dropDownList = Selector('.chosen-results')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t.click(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_font__chosen'))

  // Assertions
  await t
    .expect(dropDownList.exists).ok()
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
