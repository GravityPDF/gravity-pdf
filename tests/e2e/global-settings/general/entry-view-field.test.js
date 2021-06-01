import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Entry View Field Test`

test('should display Entry View field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Entry View').exists).ok()
    .expect(run.viewOption.exists).ok()
    .expect(run.downlaodOption.exists).ok()
    .expect(fieldDescription('Select the default action used when accessing a PDF from the Gravity Forms entries list page.', 'label').exists).ok()
})

test('should display "Download PDF" as an option on the Entry List page instead of "View PDF" when "Download" is selected', async t => {
  // Get Selectors
  const downloadPdfLink = Selector('a').withText('Download PDF')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(run.downlaodOption)
    .click(run.saveButton)
    .wait(1000)
  await run.navigatePdfEntries('gf_entries&id=3')
  await t
    .hover(run.entryItem)
    .expect(downloadPdfLink.exists).ok()
})

test('should display "View PDF" as an option on the Entry List page instead of "Download PDF" when "View" is selected', async t => {
  // Get Selectors
  const viewPdfLink = Selector('a').withText('View PDF')

  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')
  await t
    .click(run.viewOption)
    .click(run.saveButton)
    .wait(1000)
  await run.navigatePdfEntries('gf_entries&id=3')
  await t
    .hover(run.entryItem)
    .expect(viewPdfLink.exists).ok()
})
