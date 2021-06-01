import { Selector } from 'testcafe'
import { fieldLabel } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Installation Status Field Test`

test('should display Installation Status Field', async t => {
  // Get selectors
  const pdfSystemStatusTable = Selector('#pdf-system-status')
  const installationFirstLabelResult = Selector('#pdf-system-status').find('td').nth(0)
  const installationSecondLabelResult = Selector('#pdf-system-status').find('td').nth(1)
  const installationThirdLabelResult = Selector('#pdf-system-status').find('td').nth(2)
  const installationFourthLabelResult = Selector('#pdf-system-status').find('td').nth(3)
  const installationFifthLabelResult = Selector('#pdf-system-status').find('td').nth(4)

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Installation Status', 'span').exists).ok()
    .expect(pdfSystemStatusTable.exists).ok()
    .expect(fieldLabel('WP Memory Available').exists).ok()
    .expect(installationFirstLabelResult.exists).ok()
    .expect(fieldLabel('WordPress Version').exists).ok()
    .expect(installationSecondLabelResult.exists).ok()
    .expect(fieldLabel('Gravity Forms Version').exists).ok()
    .expect(installationThirdLabelResult.exists).ok()
    .expect(fieldLabel('PHP Version').exists).ok()
    .expect(installationFourthLabelResult.exists).ok()
    .expect(fieldLabel('Direct PDF Protection').exists).ok()
    .expect(installationFifthLabelResult.exists).ok()
})
