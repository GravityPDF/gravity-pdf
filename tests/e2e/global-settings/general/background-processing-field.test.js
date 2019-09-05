import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Background Processing Field Test`

test('should display Background Processing field', async t => {
  // Get selectors
  const yes = Selector('div').find('[class^="gfpdf_settings_background_processing"][value="Enable"]')
  const no = Selector('div').find('[class^="gfpdf_settings_background_processing"][value="Disable"]')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Background Processing').exists).ok()
    .expect(yes.exists).ok()
    .expect(no.exists).ok()
    .expect(fieldDescription('When enable, form submission and resending notifications with PDFs are handled in a background task. Requires Background tasks to be enabled.', 'label').exists).ok()
})
