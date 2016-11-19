import React from 'react'
import { render } from 'enzyme'

import TemplateScreenshot from '../../../../src/assets/js/react/components/TemplateScreenshot'

describe('<TemplateScreenshot />', () => {
  it('renders a blank div', () => {
    const comp = render(<TemplateScreenshot />)
    expect(comp.find('div.blank')).to.have.length(1)
    expect(comp.find('img')).to.have.length(0)
  })

  it('renders the screenshot', () => {
    const comp = render(<TemplateScreenshot image="myimage.jpg" />)
    expect(comp.find('img').attr('src')).to.equal('myimage.jpg')
  })
})