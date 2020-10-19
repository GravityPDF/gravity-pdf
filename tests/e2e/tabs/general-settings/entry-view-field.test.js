import { Selector } from 'testcafe'
import { fieldHeaderTitle, fieldDescription } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - Entry view field test`

test('should display \'Entry View\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Entry View').exists).ok()
    .expect(fieldDescription('Select the default action used when accessing a PDF from the Gravity Forms entries list page.', 'label').exists).ok()
    .expect(run.entryViewViewOption.exists).ok()
    .expect(run.entryViewDownlaodOption.exists).ok()
})

test('should save toggled value for \'Entry View\' field', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.entryViewDownlaodOption)
    .click(run.saveSettings)
    .expect(run.entryViewViewOption.checked).notOk()
    .expect(run.entryViewDownlaodOption.checked).ok()
    .click(run.entryViewViewOption)
    .click(run.saveSettings)
    .expect(run.entryViewDownlaodOption.checked).notOk()
    .expect(run.entryViewViewOption.checked).ok()
})

test('should display "Download PDF" as an option on the Entry List page instead of "View PDF" when "Download" is selected', async t => {
  // Selectors
  const downloadPdfLink = Selector('a').withText('Download PDF')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.entryViewDownlaodOption)
    .click(run.saveSettings)
  await run.navigatePdfEntries('gf_entries&id=3')
  await t.hover(run.viewEntryItem)

  // Assertions
  await t.expect(downloadPdfLink.exists).ok()
})

test('should display "View PDF" as an option on the Entry List page instead of "Download PDF" when "View" is selected', async t => {
  // Selectors
  const viewPdfLink = Selector('a').withText('View PDF')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.entryViewViewOption)
    .click(run.saveSettings)
  await run.navigatePdfEntries('gf_entries&id=3')
  await t.hover(run.viewEntryItem)

  // Assertions
  await t.expect(viewPdfLink.exists).ok()
})
