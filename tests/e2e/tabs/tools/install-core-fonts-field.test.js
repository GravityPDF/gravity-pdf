import { RequestMock } from 'testcafe'
import { baseURL } from '../../auth'
import { fieldHeaderTitle, fieldDescription } from '../../utilities/page-model/helpers/field'
import Tools from '../../utilities/page-model/tabs/tools'

const run = new Tools()
const mockSuccess = RequestMock()
  .onRequestTo(`${baseURL}/wp-admin/admin-ajax.php`)
  .respond({}, 200, { 'access-Control-Allow-Origin': '*' })

fixture`Tools tab - Install core fonts field test`
  .requestHooks(mockSuccess)

test('should display \'Install Core Fonts\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Install Core Fonts').exists).ok()
    .expect(fieldDescription('Automatically install the core fonts needed to generate PDF documents. This action only needs to be run once, as the fonts are preserved during plugin updates.', 'span').exists).ok()
    .expect(run.downloadCoreFontsButton.exists).ok()
})

test('should return download core fonts successful response ', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
  await t
    .click(run.downloadCoreFontsButton)
    .wait(400)

  // Assertions
  await t
    .expect(run.pendingResult.exists).ok()
    .expect(run.downloadSuccess.exists).ok()
    .expect(run.allSuccessfullyIntalled.exists).ok()
})

const mockFailure = RequestMock()
  .onRequestTo(`${baseURL}/wp-admin/admin-ajax.php`)
  .respond({}, 500, { 'access-Control-Allow-Origin': '*' })

fixture``
  .requestHooks(mockFailure)

test('should return download core fonts error/failed response', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
  await t
    .click(run.downloadCoreFontsButton)
    .wait(400)

  // Assertions
  await t
    .expect(run.pendingResult.exists).ok()
    .expect(run.downloadFailed.exists).ok()
    .expect(run.retryDownload.exists).ok()
})
