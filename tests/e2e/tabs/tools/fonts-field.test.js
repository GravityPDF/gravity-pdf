import { button, fieldHeaderTitle, fieldDescription } from '../../utilities/page-model/helpers/field'
import Tools from '../../utilities/page-model/tabs/tools'
import FontManager from '../../utilities/page-model/helpers/font-manager'

const run = new Tools()
const fontManager = new FontManager()

fixture`Tools tab - Fonts field test`

test('should display \'Fonts\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Fonts').exists).ok()
    .expect(fieldDescription('Install custom fonts for use in your PDF documents. Only .ttf font files are supported.', 'span').exists).ok()
    .expect(button('Advanced').exists).ok()
})

test('should check that font manager popup exist', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')

  // Assertions
  await t
    .expect(fontManager.popupHeaderText.exists).ok()
    .expect(fontManager.searchBar.exists).ok()
    .expect(fontManager.fontListColumn.exists).ok()
    .expect(fontManager.addFontColumn.exists).ok()
})

test('should display error validation', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t.click(fontManager.addFontButton)

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
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t
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
  // Actions && Assertions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t
    .click(fontManager.fontListItem.nth(0))
    .expect(fontManager.updateFontButton.hasAttribute('disabled')).ok()
    .typeText(fontManager.updateFontNameInputField, ' new', { paste: true })
    .expect(fontManager.updateFontButton.hasAttribute('disabled')).notOk()
})

test('should successfully close \'update font\' panel using cancel button', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t
    .click(fontManager.fontListItem.nth(0))
    .click(fontManager.cancelButton)

  // Assertions
  await t.expect(fontManager.updateFontPanel.exists).notOk()
})

test('should successfully perform font search', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t.typeText(fontManager.searchBar, 'Roboto', { paste: true })

  // Assertions
  await t.expect(fontManager.fontListItem.count).eql(1)
})

test('should successfully edit existing font', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t
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
    .expect(fontManager.fontListItem.nth(0).find('[class^="font-name"]').withText('Gotham 2').exists).ok()
    .expect(fontManager.fontListItem.nth(0).find('[class^="dashicons dashicons-yes"]').count).eql(4)
})

test('should successfully delete font', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t
    .click(fontManager.fontListItem.nth(1).find('[class^="dashicons dashicons-trash"]'))
    .click(fontManager.fontListItem.nth(0).find('[class^="dashicons dashicons-trash"]'))

  // Assertions
  await t
    .expect(fontManager.fontListItem.count).eql(0)
    .expect(fontManager.fontListEmptyMessage.exists).ok()
})

test('should be able to close font manager popup', async t => {
  // Actions
  await fontManager.navigateFontManager('gf_settings&subview=PDF&tab=tools#')
  await t.click(fontManager.closeDialog)

  // Assertions
  await t.expect(fontManager.fontManagerPopup.exists).notOk()
})
