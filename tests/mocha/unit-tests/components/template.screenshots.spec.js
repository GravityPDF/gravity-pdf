import React from 'react'
import { render } from 'enzyme'

import TemplateScreenshots from '../../../../src/assets/js/components/TemplateScreenshots'

describe('<TemplateScreenshots />', () => {

  it('renders wrapper divs', () => {
    const comp = render(<TemplateScreenshots />)
    expect(comp.find('.theme-screenshots')).to.have.length(1)
    expect(comp.find('.screenshot')).to.have.length(1)
  })

  it('renders a blank div', () => {
    const comp = render(<TemplateScreenshots />)
    expect(comp.find('div.blank')).to.have.length(1)
    expect(comp.find('img')).to.have.length(0)
  })

  it('renders the screenshot', () => {
    const comp = render(<TemplateScreenshots image="myimage.jpg" />)
    expect(comp.find('img').attr('src')).to.equal('myimage.jpg')
  })
})