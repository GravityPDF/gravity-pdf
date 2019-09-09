import { initialiseCommonElements } from '../../../../../../src/assets/js/admin/settings/common/initialiseCommonElements'

describe('initialiseCommonElements.js', () => {
  it('should run the function runElements()', () => {
    let method = sinon.spy(initialiseCommonElements, 'runElements')
    expect(method).to.be.a('function')
  })
})
