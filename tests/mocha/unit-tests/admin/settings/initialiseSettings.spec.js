import { initialiseSettings } from '../../../../../src/assets/js/admin/settings/initialiseSettings'

describe('initialiseSettings.js', () => {
  describe('init()', () => {
    it('should run the function initialiseSettings()', () => {
      let method = sinon.spy(initialiseSettings, 'init')
      expect(method).to.be.a('function')
    })
  })

  describe('getCurrentSettingsPage()', () => {
    it('should run the function getCurrentSettingsPage()', () => {
      let method = sinon.spy(initialiseSettings, 'getCurrentSettingsPage')
      expect(method).to.be.a('function')
    })
  })

  describe('processSettings()', () => {
    it('should run the function processSettings()', () => {
      let method = sinon.spy(initialiseSettings, 'processSettings')
      expect(method).to.be.a('function')
    })
  })

  describe('processFormSettings()', () => {
    it('should run the function processFormSettings()', () => {
      let method = sinon.spy(initialiseSettings, 'processFormSettings')
      expect(method).to.be.a('function')
    })
  })
})
