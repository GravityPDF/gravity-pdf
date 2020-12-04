import { fieldHeaderTitle, fieldDescription } from '../../utilities/page-model/helpers/field'
import Tools from '../../utilities/page-model/tabs/tools'

const run = new Tools()

fixture`Tools tab - Uninstall Gravity PDF field test`

test('should display \'Uninstall Gravity PDF\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
  await t.click(run.uninstallGravityPdfCollapsiblePanel)

  // Assertions
  await t
    .expect(fieldHeaderTitle('Uninstall Gravity PDF').exists).ok()
    .expect(fieldDescription('This operation deletes ALL Gravity PDF settings and deactivates the plugin. If you continue, all settings, configuration, custom templates and fonts will be removed.', 'p').exists).ok()
    .expect(run.uninstallGravityPdfButton.exists).ok()
})
