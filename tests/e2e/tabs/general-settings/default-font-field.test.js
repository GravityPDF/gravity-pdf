import {
  fieldLabel,
  fieldDescription,
  dropdownOptionGroup,
  dropdownOption
} from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'
import FontManager from '../../utilities/page-model/helpers/font-manager'

const run = new General()
const fontManager = new FontManager()

fixture`General settings tab - Default font field test`

test('should display \'Default Font\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Font').exists).ok()
    .expect(fieldDescription('Set the default font type used in PDFs. Choose an existing font or install your own.', 'label').exists).ok()
    .expect(run.defaultFontSelectBox.exists).ok()
    .expect(fontManager.advancedButton.exists).ok()
})

test('should display a dropdown of default fonts option', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(run.defaultFontSelectBox)

  // Assertions
  await t
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

test('should save selected font', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.defaultFontSelectBox)
    .click(dropdownOption('Pothana2000'))
    .click(run.defaultFontSelectBox)
    .click(dropdownOption('Lohit Kannada'))
    .click(run.saveSettings)

  // Assertions
  await t.expect(run.defaultFontSelectBox.value).eql('lohitkannada')
})

test('should check that font manager popup exist', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(fontManager.advancedButton)

  // Assertions
  await t
    .expect(fontManager.popupHeaderText.exists).ok()
    .expect(fontManager.searchBar.exists).ok()
    .expect(fontManager.fontListColumn.exists).ok()
    .expect(fontManager.addFontColumn.exists).ok()
})

test('should display font manager error validation', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .click(fontManager.addFontButton)

  // Assertions
  await t
    .expect(fontManager.fontNameFieldErrorValidation.exists).ok()
    .expect(fontManager.fontNameFieldErrorValidationMessage.exists).ok()
    .expect(fontManager.dropzoneRequiredRegularField.exists).ok()
    .expect(fontManager.dropzoneErrorValidationMessage.exists).ok()
    .expect(fontManager.addUpdatePanelGeneralErrorValidationMessage.exists).ok()
})

test('should successfully add new font', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .typeText(fontManager.addFontNameInputField, 'Gotham', { paste: true })
    .setFilesToUpload(fontManager.addNewFontRegular, fontManager.gothamFontRegular)
    .click(fontManager.addFontButton)
    .click(fontManager.fontListItem.nth(0))
    .typeText(fontManager.addFontNameInputField, 'Roboto', { paste: true })
    .setFilesToUpload(fontManager.addNewFontRegular, fontManager.robotoFontRegular)
    .click(fontManager.addFontButton)

  // Assertions
  await t
    .expect(fontManager.fontListItem.count).eql(2)
    .expect(fontManager.successMessage.exists).ok()
})

test('should successfully check toggled state for disabled \'Update Font\' button', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .click(fontManager.fontListItem.nth(0))
    .expect(fontManager.updateFontButton.hasAttribute('disabled')).ok()
    .typeText(fontManager.updateFontNameInputField, ' new', { paste: true })
    .expect(fontManager.updateFontButton.hasAttribute('disabled')).notOk()
})

test('should successfully close \'update font\' panel using cancel button', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .click(fontManager.fontListItem.nth(0))
    .click(fontManager.cancelButton)

  // Assertions
  await t.expect(fontManager.updateFontPanel.exists).notOk()
})

test('should successfully perform font search', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .typeText(fontManager.searchBar, 'Roboto', { paste: true })

  // Assertions
  await t.expect(fontManager.fontListItem.count).eql(1)
})

test('should successfully edit existing font', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .click(fontManager.fontListItem.nth(0))
    .click(fontManager.updateFontNameInputField)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(fontManager.updateFontNameInputField, 'Gotham 2', { paste: true })
    .setFilesToUpload(fontManager.addNewFontItalics, fontManager.robotoFontItalics)
    .setFilesToUpload(fontManager.addNewFontBold, fontManager.robotoFontBold)
    .setFilesToUpload(fontManager.addNewFontBoldItalics, fontManager.robotoFontBoldItalics)
    .click(fontManager.updateFontButton)

  // Assertions
  await t
    .expect(fontManager.successMessage.exists).ok()
    .expect(fontManager.fontName.withText('Gotham 2').exists).ok()
    .expect(fontManager.fontVariantsCheck.count).eql(4)
})

test('should successfully delete font', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .click(fontManager.fontListItem.nth(1).find('[class^="dashicons dashicons-trash"]'))
    .click(fontManager.fontListItem.nth(0).find('[class^="dashicons dashicons-trash"]'))

  // Assertions
  await t
    .expect(fontManager.fontListItem.count).eql(0)
    .expect(fontManager.fontListEmptyMessage.exists).ok()
})

test('should be able to close font manager popup', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(fontManager.advancedButton)
    .click(fontManager.closeDialog)

  // Assertions
  await t.expect(fontManager.fontManagerPopup.exists).notOk()
})
