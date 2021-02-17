import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription, button } from '../../../page-model/helpers/field'
import Fonts from '../../../page-model/global-settings/tools/fonts/fonts'

const run = new Fonts()

fixture`Tools Tab - Manage Fonts Test`

test('should open Manage Fonts Popup Box', async t => {
  // Get selectors
  const visibleManageFontsPopupBox = run.manageFontsPopupBox.filterVisible()
  const dialogTitle = Selector('span').withText('Manage Fonts')
  const addFontText = Selector('span').withText('ADD FONT')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')

  // Assertions
  await t
    .expect(visibleManageFontsPopupBox.count).eql(1)
    .expect(dialogTitle.exists).ok()
    .expect(fieldDescription('Manage all your custom Gravity PDF fonts in one place. Only .ttf font files are supported and they MUST be uploaded through your media library (no external links).', 'div').exists).ok()
    .expect(run.addFontIcon.exists).ok()
    .expect(addFontText.exists).ok()
})

test('should open Manage Fonts Popup Box that can be close', async t => {
  // Get selectors
  const closeButton = Selector('div').withText('Manage Fonts').nth(10).find('button[title="Close"]')
  const hidePopupBox = run.manageFontsPopupBox.filterHidden()

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(closeButton)
    .expect(hidePopupBox.count).eql(1)
})

test('should open Add Font Dialog Box Settings', async t => {
  // Get selectors
  const addFontDialogBox = Selector('.font-settings').filterVisible()
  const inputFieldOne = Selector('[name="font_name"].regular-text.font-name-field')
  const inputFielTwo = Selector('[name="regular"].regular-text')
  const inputFieldThree = Selector('[name="italics"].regular-text')
  const inputFieldFour = Selector('[name="bold"].regular-text')
  const inputFieldFive = Selector('[name="bolditalics"].regular-text')
  const selectFontButton = Selector('input').withAttribute('data-uploader-title', 'Select Font')
  const wpMediaModal = Selector('.media-modal')
  const showWPmediaModal = wpMediaModal.filterVisible()

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .click(selectFontButton)

  // Assertions
  await t
    .expect(addFontDialogBox.count).eql(1)
    .expect(fieldLabel('Font Name ', 'label').exists).ok()
    .expect(fieldLabel('Regular ', 'label').exists).ok()
    .expect(fieldLabel('Italics', 'label').exists).ok()
    .expect(fieldLabel('Bold', 'label').exists).ok()
    .expect(fieldLabel('Bold Italics', 'label').exists).ok()
    .expect(fieldDescription('Only alphanumeric characters and spaces are accepted.').exists).ok()
    .expect(inputFieldOne.exists).ok()
    .expect(inputFielTwo.exists).ok()
    .expect(inputFieldThree.exists).ok()
    .expect(inputFieldFour.exists).ok()
    .expect(inputFieldFive.exists).ok()
    .expect(button('Save Font').exists).ok()
    .expect(showWPmediaModal.count).eql(1)
})

test('should display multiple Add Font Dialog Box Settings', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .click(run.addFontIcon)

  // Assertions
  await t
    .expect(run.fontList.child('li').nth(0).exists).ok()
    .expect(run.fontList.child('li').nth(1).exists).ok()
})

test('should display Add Font Dialog Box Settings Font Name Field RED Box Error \'Only alphanumeric characters and spaces are accepted\'', async t => {
  // Get selectors
  const fontInputField = Selector('input').withAttribute('name', 'font_name')
  const redInputBox = Selector('div').find('[class^="regular-text font-name-field"][style="border-color: red;"]')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .typeText(fontInputField, 's$$$', { paste: true })
    .expect(redInputBox.exists).ok()
})

test('should display Add Font Dialog Box Settings error message when font file inputed is not TFF', async t => {
  // Get selectors
  const regularFontField = Selector('input').withAttribute('name', 'regular')
  const errorMessage = Selector('label').withText('Only TTF font files are supported.')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .typeText(regularFontField, 'https://gravitypdf.com/Gotham-Black-Regular.otf', { paste: true })
    .click(button('Save Font'))
    .expect(errorMessage.exists).ok()
})

test('should open Add Font Dialog Box Settings that can be minimize', async t => {
  // Get selectors
  const minimizeIcon = Selector('div').find('[class^="fa fa-angle-right"]')
  const addFontDialogBox = Selector('div').find('[class^="font-settings"]').filterHidden()

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .click(minimizeIcon)
    .expect(addFontDialogBox.count).eql(1)
})

test('should open Add Font Dialog Box Settings with a confirmation Popup to delete', async t => {
  // Get selectors
  const visibleConfirmDeletePopupBox = run.confirmDeletePopupBox.filterVisible()
  const dialogTitle = Selector('span').withText('Delete Font?')
  const contentText = Selector('div').withText('Warning! You are about to delete this Font. Select \'Delete\' to delete, \'Cancel\' to stop.')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .click(run.deleteIcon)

  // Assertions
  await t
    .expect(visibleConfirmDeletePopupBox.count).eql(1)
    .expect(dialogTitle.exists).ok()
    .expect(contentText.exists).ok()
    .expect(button('Delete').exists).ok()
    .expect(button('Cancel').exists).ok()
})

test('should open Add Font Dialog Box Settings delete popup box that can be close', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .click(run.deleteIcon)
    .click(run.cancelButton)
    .expect(run.confirmDeletePopupBox.exists).notOk()
})

test('should open Add Font Dialog Box Settings that can be deleted', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#/')
  await t
    .click(run.addFontIcon)
    .click(run.deleteIcon)
    .click(button('Delete'))
    .expect(run.fontListEmpty.exists).ok()
})
