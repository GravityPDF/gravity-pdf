import { toolsSettings } from '../../../../../../src/assets/js/admin/settings/global/toolsSettings'

describe('toolsSettings.js', () => {
  it('should run the function runSetup()', () => {
    let method = sinon.spy(toolsSettings, 'runSetup')
    expect(method).to.be.a('function')
  })
})
