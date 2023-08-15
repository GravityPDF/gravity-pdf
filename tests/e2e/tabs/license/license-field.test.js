import { fieldHeaderTitle } from '../../utilities/page-model/helpers/field'
import License from '../../utilities/page-model/tabs/license'
import Pdf from '../../utilities/page-model/helpers/pdf'

const license = new License()
const pdf = new Pdf()

fixture`License tab - License field test`

test('should display \'License\' field information', async t => {
  // Actions
  await pdf.navigate('gf_settings&subview=PDF&tab=license')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Licensing').exists).ok()
    .expect(license.saveSettings.exists).ok()
})
