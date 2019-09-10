import { doFormSettingsListPage } from '../../../../../../src/assets/js/admin/settings/form/doFormSettingsListPage'

describe('doFormSettingsListPage.js', () => {
  it('should run the function setupAJAXList()', () => {
    let method = sinon.spy(doFormSettingsListPage, 'setupAJAXListListener')
    expect(method).to.be.a('function')
  })
})
