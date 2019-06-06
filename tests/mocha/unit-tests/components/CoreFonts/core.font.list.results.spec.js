import React from 'react'
import { shallow, mount } from 'enzyme'
import { createHashHistory } from 'history'
import CoreFontListResults from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontListResults'

describe('<CoreFontListResults />', () => {

  it('Render nothing', () => {
    const comp = shallow(<CoreFontListResults console={{}} retry={[]} />)

    expect(comp.html()).to.equal(null)
  })

  it('Render console messages', () => {
    const consoleList = {
      'Item1': { status: 'success1', message: 'Message1' },
      'Item2': { status: 'success2', message: 'Message2' },
      'Item3': { status: 'success3', message: 'Message3' }
    }
    const comp = shallow(<CoreFontListResults console={consoleList} retry={[]} />)

    expect(comp.find('.gfpdf-core-font-status-success3').length).to.equal(1)
    expect(comp.find('.gfpdf-core-font-status-success3').text()).to.equal('Message3 ')
    expect(comp.find('.gfpdf-core-font-status-success2').length).to.equal(1)
    expect(comp.find('.gfpdf-core-font-status-success3').length).to.equal(1)
  })

  it('Include Spacer', () => {
    const comp = mount(<CoreFontListResults console={{ completed: { status: '', message: '' } }} retry={[]} />)

    expect(comp.find('.gfpdf-core-font-spacer').length).to.equal(1)
  })

  it('Include Retry Link', () => {
    const History = createHashHistory()
    const comp = mount(
      <CoreFontListResults
        history={History}
        retryText='Retry!'
        console={{ completed: { status: '', message: '' } }}
        retry={['']}
      />
    )
    const link = comp.find('a')

    expect(link.length).to.equal(1)

    link.simulate('click')

    expect(window.location.hash).to.equal('#/retryDownloadCoreFonts')
  })
})
