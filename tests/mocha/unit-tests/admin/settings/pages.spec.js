import { pages } from '../../../../../src/assets/js/admin/settings/pages'

describe('pages.js', () => {
  it('check pages.isSettings() if it returns an integer', () => {
    expect(pages.isSettings()).to.satisfy(Number.isInteger)
  })

  it('check pages.isFormSettings() if it returns an integer', () => {
    expect(pages.isFormSettings()).to.satisfy(Number.isInteger)
  })

  it('check pages.isFormSettingsList() if it returns an integer', () => {
    expect(pages.isFormSettingsList()).to.satisfy(Number.isInteger)
  })

  it('check pages.isFormSettingsEdit() if it returns an integer', () => {
    expect(pages.isFormSettingsEdit()).to.satisfy(Number.isInteger)
  })
})
