import { Selector } from 'testcafe'
import {
  fieldLabel,
  fieldDescription,
  selectBox,
  dropdownOptionGroup,
  dropdownOption,
  button,
  templateDetails
} from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'
import { baseURL } from '../../auth'

const run = new General()

fixture`General Tab - Default Template Field Test`

test('should display \'Default Template Field\'', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Template').exists).ok()
    .expect(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_template__chosen').exists).ok()
    .expect(fieldDescription('Choose an existing template or purchased more from our template shop. You can also build your own or hire us to create a custom solution.', 'label').exists).ok()
    .expect(button('Advanced').exists).ok()
})

test('should display the Core Templates dropdown option', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')

  // Assertions
  await t
    .expect(dropdownOptionGroup('Core').exists).ok()
    .expect(dropdownOption('Blank Slate').exists).ok()
    .expect(dropdownOption('Focus Gravity').exists).ok()
    .expect(dropdownOption('Rubix').exists).ok()
    .expect(dropdownOption('Zadani').exists).ok()
})

test('should display Popup Template Selector', async t => {
  // Get selectors
  const popupHeaderText = Selector('h1').withText('Installed PDFs')
  const installedTemplatesSearchbar = Selector('#wp-filter-search-input')
  const individualThemeBox = Selector('.theme')
  const themeScreenshot = Selector('.theme-screenshot')
  const themeAuthor = Selector('.theme-author')
  const themeName = Selector('.theme-name')
  const themeSelectButton = Selector('a').withText('Select')
  const themeDetailsLink = Selector('span').withText('Template Details')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t.click(button('Advanced'))

  // Assertions
  await t
    .expect(selectBox('chosen-container chosen-container-single', 'gfpdf_settings_default_template__chosen').exists).ok()
    .expect(popupHeaderText.exists).ok()
    .expect(button('Close dialog').exists).ok()
    .expect(installedTemplatesSearchbar.exists).ok()
    .expect(individualThemeBox.exists).ok()
    .expect(themeScreenshot.exists).ok()
    .expect(themeAuthor.exists).ok()
    .expect(themeName.exists).ok()
    .expect(themeSelectButton.exists).ok()
    .expect(themeDetailsLink.exists).ok()
})

test('should display \'Add New Template Dropzone\'', async t => {
  // Get selectors
  const dropZoneBox = Selector('.dropzone')
  const addNewTemplateButton = Selector('a').withText('Add New Template').find('div').find('span')
  const installationMessage = Selector('div')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t.click(button('Advanced'))

  // Assertions
  await t
    .expect(dropZoneBox.exists).ok()
    .expect(addNewTemplateButton.exists).ok()
    .expect(installationMessage.innerText).contains('If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).')
})

test('should display individual specific Template details', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(button('Advanced'))
    .click(run.focusGravity)
  let imageScreenshot = await Selector('.screenshot').find('img').getAttribute('src')

  // Assertions
  await t
    .expect(imageScreenshot).contains('focus-gravity.png')
    .expect(templateDetails('theme-name', 'Focus Gravity').exists).ok()
    .expect(templateDetails('theme-version', 'Version: ').exists).ok()
    .expect(templateDetails('theme-author', 'Gravity PDF').exists).ok()
    .expect(templateDetails('theme-author', 'Group: Core').exists).ok()
    .expect(templateDetails('theme-description', 'Focus Gravity providing a classic layout which epitomises Gravity Forms Print Preview. It\'s the familiar layout you\'ve come to love. Through the Template tab you can control the PDF header and footer, change the background color or image, and show or hide the form title, page names, HTML fields and the Section Break descriptions.').exists).ok()
    .expect(templateDetails('theme-tags', 'Tags: Header, Footer, Background, Optional HTML Fields, Optional Page Fields, Combined Row, Alternate Colors').exists).ok()
})

test('should navigate to next and previous Template', async t => {
  // Get Selectors
  const rubixTemplate = Selector('div').find('[class^="theme-name"]').withText('Rubix')
  const focusGravityTemplate = Selector('div').find('[class^="theme-name"]').withText('Focus Gravity')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(button('Advanced'))
    .click(run.focusGravity)
    .click(button('Show next template'))
    .expect(rubixTemplate.exists).ok()
    .click(button('Show previous template'))
    .expect(focusGravityTemplate.exists).ok()
    .pressKey('right')
    .expect(rubixTemplate.exists).ok()
    .pressKey('left')
    .expect(focusGravityTemplate.exists).ok()
})

test('should display Popup Template Selector that can be close', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(button('Advanced'))
    .click(button('Close dialog'))
    .click(button('Advanced'))
    .pressKey('esc')
    .expect(run.templatePopupBox.exists).notOk()
})

test('should display Template filter search bar', async t => {
  // Get Selectors
  const templateSearchbar = Selector('#wp-filter-search-input')
  const searchResult = Selector('.theme-author')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(button('Advanced'))
    .typeText(templateSearchbar, 'rubix', { paste: true })

  // Assertions
  await t
    .expect(templateSearchbar.exists).ok()
    .expect(searchResult.count).eql(1)
})

test('should successfully upload a new Template', async t => {
  // Get Selectors
  const testTemplate = '../../resources/test-template.zip'
  const imageScreenshot = Selector('.theme-screenshot').find('img').withAttribute('src', `${baseURL}/wp-content/uploads/PDF_EXTENDED_TEMPLATES/images/test-template.png`)

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(button('Advanced'))
    .setFilesToUpload(run.addNewTemplate, testTemplate)

  // Assertions
  await t
    .expect(templateDetails('notice inline', 'Template successfully installed').exists).ok()
    .expect(imageScreenshot.exists).ok()
    .expect(templateDetails('theme-author', 'Custom').exists).ok()
    .expect(templateDetails('theme-name', 'Test Template').exists).ok()
    .expect(templateDetails('notice inline', 'PDF Template(s) Successfully Installed / Updated').exists).ok()
})

test('should successfully delete the new added template', async t => {
  // Get Selectors
  const deleteButton = Selector('a').withText('Delete').nth(0)
  const imageScreenshot = Selector('.theme-screenshot').find('img').withAttribute('src', `${baseURL}/wp-content/uploads/PDF_EXTENDED_TEMPLATES/images/test-template.png`)

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .setNativeDialogHandler(() => true)
    .click(button('Advanced'))
    .click(run.testTemplateDetailsLink)
    .click(deleteButton)

  // Assertions
  await t
    .expect(imageScreenshot.exists).notOk()
    .expect(templateDetails('theme-author', 'Universal (Premium)').exists).notOk()
    .expect(templateDetails('theme-name', 'Cellulose').exists).notOk()
})
