import React from 'react'
import { mount } from 'enzyme'
import TemplateScreenshot from '../../../../../src/assets/js/react/components/Template/TemplateScreenshot'

describe('<TemplateScreenshot />', () => {

  it('renders a blank div', () => {
    const comp = mount(<TemplateScreenshot />)

    expect(comp.find('div.blank')).to.have.length(1)
    expect(comp.find('img')).to.have.length(0)
  })

  it('renders the screenshot', () => {
    const comp = mount(<TemplateScreenshot image='base/src/assets/images/paws-with-logo-small.png' />)

    expect(comp.find('img').render().attr('src')).to.equal('base/src/assets/images/paws-with-logo-small.png')
  })
})
