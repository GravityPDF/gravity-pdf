import { fieldHeaderTitle, fieldDescription } from '../../utilities/page-model/helpers/field'
import License from '../../utilities/page-model/tabs/license'

const run = new License()

fixture`License tab - License field test`

test('should display \'License\' field information', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=license')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Licensing').exists).ok()
    .expect(fieldDescription('To take advantage of automatic updates for your Gravity PDF add-ons, enter your license key(s) below. Your license key is located in your Purchase Confirmation email you received after you bought the add-on.', 'p').exists).ok()
    .expect(fieldDescription('By installing a Gravity PDF extension you are automatically giving permission for us to periodically poll GravityPDF.com via HTTPS for your current license status and any new plugin updates. The only personal data sent is your website domain name and license key. To opt-out you will need to deactivate all Gravity PDF extensions.', 'p').exists).ok()
    .expect(run.saveSettings.exists).ok()
})
