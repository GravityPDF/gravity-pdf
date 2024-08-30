import Pdf from '../utilities/page-model/helpers/pdf'
import { RequestLogger } from 'testcafe'

const run = new Pdf()
const downloadLogger = RequestLogger(null, { logResponseBody: true, logResponseHeaders: true })

fixture`PDF Preview test`

test('should open PDF preview in new tab', async t => {
  // Actions
  await run.addNewPdf(1)

  downloadLogger.clear()

  // Assertions
  await t
    .addRequestHooks(downloadLogger)
    .click(run.previewSettings)
    .wait(500)
    .removeRequestHooks(downloadLogger)

  await t
    .expect(downloadLogger.contains(r => r.request.url.endsWith('gravity-pdf/v1/form/1/schema/?template=zadani'))).ok()
    .expect(downloadLogger.contains(r => r.request.url.endsWith('gravity-pdf/v1/form/1/preview'))).ok()
    .expect(downloadLogger.contains(r => r.response.headers['content-disposition'] === 'inline; filename="document.pdf"; filename*=utf-8\'\'document.pdf')).ok()
    .expect(downloadLogger.contains(r => r.response.headers['content-type'] === 'application/pdf')).ok()
})
