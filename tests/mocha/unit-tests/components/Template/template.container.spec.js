import React from 'react'
import { shallow } from 'enzyme'

import TemplateContainer from '../../../../../src/assets/js/react/components/Template/TemplateContainer'

describe('<TemplateContainer />', () => {

  it('the container renders correctly', () => {
    const comp = shallow(<TemplateContainer
    header="Header Prop"
    footer="Footer Prop"
    children="Children Prop" />, { disableLifecycleMethods: true })

    expect(comp.find('div.theme-backdrop')).to.have.length(1)
    expect(comp.find('div.theme-wrap')).to.have.length(1)
    expect(comp.find('div.theme-header')).to.have.length(1)
    expect(comp.find('#gfpdf-template-container')).to.have.length(1)

    expect(comp.find('.theme-header').text()).to.have.string('Header Prop')
    expect(comp.find('#gfpdf-template-container').text()).to.have.string('Children Prop')
    expect(comp.find('.theme-wrap').text()).to.have.string('Footer Prop')
  })
})