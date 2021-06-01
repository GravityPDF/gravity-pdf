import Help from '../../page-model/global-settings/help/help'

const run = new Help()

fixture`Help Tab - Help Search Bar Test`

test('should check if the help search bar exist', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=help')

  // Assertions
  await t.expect(run.searchBar.exists).ok()
})
