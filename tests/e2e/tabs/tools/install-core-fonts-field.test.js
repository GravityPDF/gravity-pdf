import { RequestMock } from 'testcafe'
import { baseURL } from '../../auth'
import { fieldHeaderTitle } from '../../utilities/page-model/helpers/field'
import Tools from '../../utilities/page-model/tabs/tools'

const run = new Tools()

fixture`Tools tab - Core Fonts`

test('should display \'Install Core Fonts\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')

  // Assertions
  await t
    .expect(fieldHeaderTitle('Install Core Fonts').exists).ok()
    .expect(run.downloadCoreFontsButton.exists).ok()
})

const mockSuccess = RequestMock()
  .onRequestTo(`${baseURL}/wp-admin/admin-ajax.php`)
  .respond({}, 200, { 'access-Control-Allow-Origin': '*' })

test
  .requestHooks(mockSuccess)('should return download core fonts successful response ', async t => {
    // Actions
    await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
    await t.click(run.downloadCoreFontsButton)

    // Assertions
    await t
      .expect(run.pendingResult.exists).ok()
      .expect(run.downloadSuccess.exists).ok()
      .expect(run.allSuccessfullyIntalled.exists).ok()
  })

const mockFailure = RequestMock()
  .onRequestTo(`${baseURL}/wp-admin/admin-ajax.php`)
  .respond({}, 500, { 'access-Control-Allow-Origin': '*' })

test.requestHooks(mockFailure)('should return download core fonts error/failed response', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=tools#')
  await t.click(run.downloadCoreFontsButton)

  // Assertions
  await t
    .expect(run.pendingResult.exists).ok()
    .expect(run.downloadFailed.exists).ok()
    .expect(run.retryDownload.exists).ok()
})
