import { Selector } from 'testcafe'
import {
  fieldLabel,
  fieldDescription,
  button,
  dropdownOptionGroup,
  dropdownOption,
  templateDetails
} from '../../utilities/page-model/helpers/field'
import TemplateManager from '../../utilities/page-model/helpers/template-manager'
import General from '../../utilities/page-model/tabs/general-settings'

const templateManager = new TemplateManager()
const run = new General()

fixture`General settings tab - Default template field test`

test('should display \'Default Template\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Template').exists).ok()
    .expect(fieldDescription('Choose an existing template or purchased more from our template shop. You can also build your own or hire us to create a custom solution.').exists).ok()
    .expect(run.defaultTemplateSelectBox.exists).ok()
    .expect(templateManager.advancedButton.exists).ok()
})

test('should display the core templates dropdown option', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(dropdownOptionGroup('Core').exists).ok()
    .expect(dropdownOption('Blank Slate').exists).ok()
    .expect(dropdownOption('Focus Gravity').exists).ok()
    .expect(dropdownOption('Rubix').exists).ok()
    .expect(dropdownOption('Zadani').exists).ok()
})

test('should save selected template', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.defaultTemplateSelectBox)
    .click(dropdownOption('Rubix'))
    .click(run.saveSettings)
    .wait(500)

  // Assertions
  await t
    .expect(run.defaultTemplateSelectBox.value).eql('rubix')
})

test('should check that template manager popup exist', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t.click(templateManager.advancedButton)

  // Assertions
  await t
    .expect(templateManager.popupHeaderText.exists).ok()
    .expect(templateManager.closeDialog().exists).ok()
    .expect(templateManager.searchBar.exists).ok()
    .expect(templateManager.individualThemeBox.exists).ok()
    .expect(templateManager.themeScreenshot.exists).ok()
    .expect(templateManager.themeAuthor.exists).ok()
    .expect(templateManager.themeName.exists).ok()
    .expect(templateManager.themeSelectButton.exists).ok()
    .expect(templateManager.themeDetailsLink.exists).ok()
    .expect(templateManager.dropZoneBox.exists).ok()
    .expect(templateManager.addNewTemplateButton.exists).ok()
    .expect(templateManager.installationMessage.innerText).contains('If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).')
})

test('should display individual template details', async t => {
  // Selectors
  const imageScreenshot = Selector('.screenshot').find('img').getAttribute('src')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(templateManager.advancedButton)
    .click(templateManager.focusGravityTemplateDetails)

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

test('should navigate to next and previous template', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.defaultTemplateSelectBox)
    .click(dropdownOption('Blank Slate'))
    .click(templateManager.advancedButton)
    .click(templateManager.focusGravityTemplateDetails)
    .click(button('Show next template'))
    .expect(templateManager.rubixTemplate.exists).ok()
    .click(button('Show previous template'))
    .expect(templateManager.focusGravityTemplate.exists).ok()
    .pressKey('right')
    .expect(templateManager.rubixTemplate.exists).ok()
    .pressKey('left')
    .expect(templateManager.focusGravityTemplate.exists).ok()
})

test('should display popup template manager that can be close', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(templateManager.advancedButton)
    .click(templateManager.closeDialog())
    .click(templateManager.advancedButton)
    .pressKey('esc')
    .expect(templateManager.templatePopupBox.exists).notOk()
})

test('should successfully search for template', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(templateManager.advancedButton)
    .typeText(templateManager.templateSearchbar, 'rubix', { paste: true })

  // Assertions
  await t
    .expect(templateManager.templateSearchbar.exists).ok()
    .expect(templateManager.searchResult.count).eql(1)
})

test('should successfully upload new template', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(templateManager.advancedButton)
    .setFilesToUpload(templateManager.addNewTemplateButton, templateManager.testTemplate)

  // Assertions
  await t
    .expect(templateDetails('notice inline', 'Template successfully installed').exists).ok()
    .expect(templateManager.imageScreenshot.exists).ok()
    .expect(templateDetails('theme-author', 'Custom').exists).ok()
    .expect(templateDetails('theme-name', 'Test Template').exists).ok()
    .expect(templateDetails('notice inline', 'PDF Template(s) Successfully Installed / Updated').exists).ok()
})

test('should successfully delete new added template', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(templateManager.advancedButton)
    .click(templateManager.testTemplateDetailsLink)
    .click(templateManager.deleteButton)

  // Assertions
  await t
    .expect(templateManager.imageScreenshot.exists).notOk()
    .expect(templateDetails('theme-author', 'Universal (Premium)').exists).notOk()
    .expect(templateDetails('theme-name', 'Cellulose').exists).notOk()
})
