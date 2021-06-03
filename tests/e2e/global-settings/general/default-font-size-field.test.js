import { Selector } from 'testcafe'
import { fieldLabel, fieldDescription } from '../../page-model/helpers/field'
import General from '../../page-model/global-settings/general/general'

const run = new General()

fixture`General Tab - Default Font Size Field Test`

test('should display Default Font Size field', async t => {
  // Get selectors
  const fontSizeInputBox = Selector('#gfpdf_settings\\[default_font_size\\]')

  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('Default Font Size').exists).ok()
    .expect(fontSizeInputBox.exists).ok()
    .expect(fieldDescription('Set the default font size used in PDFs.', 'label').exists).ok()
})
