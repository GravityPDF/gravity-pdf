import { RequestMock } from 'testcafe'
import Help from '../../page-model/global-settings/help/help'

const run = new Help()
const result = [{
  link: 'https://gravitypdf.com/documentation/v5/user-hide-form-fields/',
  title: { rendered: 'Hide Form Fields' },
  excerpt: { rendered: '<p>Only certain form data is important to you. That&#8217;s why Gravity PDF has a number of ways to filter out the unimportant fields in your generated PDF. It&#8217;s important to&#8230;</p>' }
}, {
  link: 'https://gravitypdf.com/documentation/v5/user-gravity-forms-compatibility/',
  title: { rendered: 'Gravity Forms Compatibility' },
  excerpt: { rendered: '<p>Gravity PDF is a third party extension for Gravity Forms. The company who builds Gravity PDF, Blue Liquid Designs, is an independent third party who has no control over Gravity&#8230;</p>' }
}]
const mock = RequestMock()
  .onRequestTo(`https://gravitypdf.com/wp-json/wp/v2/v5_docs/?search=form`)
  .respond(result, 200, { 'access-Control-Allow-Origin': '*' })

fixture`Help Tab - Help Search Bar Test with Result`
  .requestHooks(mock)

test('should search and display existing results', async t => {
  // Actions & Assertions
  await run.navigateSettingsTab('gf_settings&subview=pdf&tab=help')
  await t
    .typeText(run.searchBar, 'form', { paste: true })
    .expect(run.resultExist.exists).ok()
})
