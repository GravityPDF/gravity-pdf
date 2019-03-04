import React from 'react'
import { mount } from 'enzyme'

import TemplateScreenshots from '../../../../../src/assets/js/react/components/Template/TemplateScreenshots'

describe('<TemplateScreenshots />', () => {

  it('renders wrapper divs', () => {
    const comp = mount(<TemplateScreenshots/>)
    expect(comp.find('.theme-screenshots')).to.have.length(1)
    expect(comp.find('.screenshot')).to.have.length(1)
  })

  it('renders a blank div', () => {
    const comp = mount(<TemplateScreenshots/>)
    expect(comp.find('div.blank')).to.have.length(1)
    expect(comp.find('img')).to.have.length(0)
  })

  it('renders the screenshot', () => {
    const comp = mount(<TemplateScreenshots image="myimage.jpg"/>)
    expect(comp.find('img').render().attr('src')).to.equal('myimage.jpg')
  })
})
