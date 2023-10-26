import {
  fieldLabel,
  mergeTagsWrapper,
  passwordGroupOption,
  passwordOptionItem
} from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Password field test`

test('should display \'Password\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t.click(run.formatStandardCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('Password').exists).ok()
    .expect(run.passwordInputBox.exists).ok()
    .expect(run.passwordMergeTagsOptionList.exists).ok()
})

test('should check that merge tags list option exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.passwordMergeTagsOptionList)

  // Assertions
  await t
    .expect(mergeTagsWrapper('gfpdf-settings-field-wrapper-password').find('li').count).gt(0)
    .expect(passwordGroupOption('Optional form fields').exists).ok()
    .expect(passwordOptionItem('Text').exists).ok()
    .expect(passwordOptionItem('Name (Prefix)').exists).ok()
    .expect(passwordGroupOption('Other').exists).ok()
    .expect(passwordOptionItem('User IP Address').exists).ok()
    .expect(passwordOptionItem('Date (mm/dd/yyyy)').exists).ok()
    .expect(passwordGroupOption('Custom').exists).ok()
    .expect(passwordOptionItem('PDF: Sample').exists).ok()
})

test('should save selected merge tags', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.passwordMergeTagsOptionList)
    .click(passwordOptionItem('Text'))
    .click(run.passwordMergeTagsOptionList)
    .click(passwordOptionItem('Name (Prefix)'))
    .click(run.saveSettings)
    .expect(run.passwordInputBox.value).contains('{Name (Prefix):2.2}{Text:1}')
    .click(run.passwordInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .typeText(run.passwordInputBox, '{Name (Prefix):2.2}', { paste: true })
    .click(run.saveSettings)
    .expect(run.passwordInputBox.value).eql('{Name (Prefix):2.2}')
    .click(run.passwordInputBox)
    .pressKey('ctrl+a')
    .pressKey('backspace')
    .click(run.saveSettings)
    .expect(run.passwordInputBox.value).eql('')
})

test('should disable/reset PDF security field and hide password field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.passwordField.visible).notOk()
})
