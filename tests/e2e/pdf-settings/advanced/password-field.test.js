import { fieldLabel, fieldDescription } from '../../utilities/page-model/helpers/field'
import Pdf from '../../utilities/page-model/helpers/pdf'

const run = new Pdf()

fixture`PDF advanced settings - Password field test`

test('should display \'Password\' field', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.formatStandardCheckbox)

  // Assertions
  await t
    .expect(fieldLabel('Password').exists).ok()
    .expect(fieldDescription('Password protect the PDF, or leave blank to disable. Mergetags are supported.', 'label').exists).ok()
    .expect(run.passwordInputBox.exists).ok()
    .expect(run.passwordMergeTagsOptionList.exists).ok()
})

test('should check that merge tags list option exist', async t => {
  // Actions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.passwordMergeTagsOptionList)

  // Assertions
  await t
    .expect(run.listItems.count).eql(26)
    .expect(run.groupHeader1.exists).ok()
    .expect(run.optionA.exists).ok()
    .expect(run.optionB.exists).ok()
    .expect(run.groupHeader2.exists).ok()
    .expect(run.optionC.exists).ok()
    .expect(run.optionD.exists).ok()
    .expect(run.groupHeader3.exists).ok()
    .expect(run.optionE.exists).ok()
})

test('should save selected merge tags', async t => {
  // Actions & Assertions
  await run.navigatePdfSection('gf_edit_forms&view=settings&subview=PDF&id=4')
  await t
    .click(run.advancedCollapsiblePanel)
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.passwordMergeTagsOptionList)
    .click(run.optionA)
    .click(run.passwordMergeTagsOptionList)
    .click(run.optionB)
    .click(run.saveSettings)
    .expect(run.passwordInputBox.value).eql('{Text:1}{Name (Prefix):2.2}')
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
    .click(run.advancedCollapsiblePanel)
    .click(run.formatStandardCheckbox)
    .click(run.enablePdfSecurityCheckbox)
    .click(run.saveSettings)

  // Assertions
  await t
    .expect(run.passwordField.visible).notOk()
})
