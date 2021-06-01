import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Debug Mode Field Test`

test('should display Debug Mode field', async t => {
  // Get selectors
  const yes = Selector('div').find('[class^="gfpdf_settings_debug_mode"][value="Yes"]')
  const no = Selector('div').find('[class^="gfpdf_settings_debug_mode"][value="No"]')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Debug Mode').exists).ok()
    .expect(yes.exists).ok()
    .expect(no.exists).ok()
    .expect(fieldDescription('When enabled, debug information will be displayed on-screen for core features.', 'label').exists).ok()
})
