import { fieldHeaderTitle } from '../../utilities/page-model/helpers/field'
import License from '../../utilities/page-model/tabs/license'

const run = new License()

fixture`License tab - License field test`

test('should display \'License\' field information', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=license')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Licensing').exists).ok()
    .expect(run.saveSettings.exists).ok()
})
