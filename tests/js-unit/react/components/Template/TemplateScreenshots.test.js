import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import TemplateScreenshots from '../../../../../src/assets/js/react/components/Template/TemplateScreenshots'

describe('Template - TemplateScreenshots.js', () => {

  test('renders <TemplateScreenshots /> component and image', () => {
    const wrapper = shallow(<TemplateScreenshots image={'test.png'} />)
    const component = findByTestAttr(wrapper, 'component-templateScreenshots')

    expect(component.length).toBe(1)
    expect(wrapper.find('img').length).toBe(1)
  })
})
