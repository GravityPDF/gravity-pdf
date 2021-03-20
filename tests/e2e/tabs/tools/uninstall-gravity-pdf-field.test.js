import Tools from '../../utilities/page-model/tabs/tools'

const run = new Tools()

fixture`Tools tab - Uninstall Gravity PDF field test`

test('should display \'Uninstall Gravity PDF\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=uninstall')

  // Assertions
  await t
    .expect(run.uninstallPanelTitle.exists).ok()
    .expect(run.uninstallPanelDescription.exists).ok()
    .expect(run.uninstallGravityPdfButton.exists).ok()
})
