import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import TemplateScreenshot from '../../../../../src/assets/js/react/components/Template/TemplateScreenshot'

describe('Template - TemplateScreenshot.js', () => {

  test('renders <TemplateScreenshot /> component and image', () => {
    const wrapper = shallow(<TemplateScreenshot image={'test.jpg'} />)
    const component = findByTestAttr(wrapper, 'component-templateScreenshot')

    expect(component.length).toBe(1)
    expect(wrapper.find('img').length).toBe(1)
  })
})
