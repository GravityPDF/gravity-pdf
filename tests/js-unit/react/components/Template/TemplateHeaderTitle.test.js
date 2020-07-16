import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import TemplateHeaderTitle from '../../../../../src/assets/js/react/components/Template/TemplateHeaderTitle'

describe('Template - TemplateHeaderTitle.js', () => {

  const wrapper = shallow(<TemplateHeaderTitle header={'Sample Text'} />)
  const component = findByTestAttr(wrapper, 'component-templateHeaderTitle')

  test('renders <TemplateHeaderTitle /> component', () => {
    expect(component.length).toBe(1)
  })

  test('renders component text', () => {
    expect(component.text()).toBe('Sample Text')
  })
})
