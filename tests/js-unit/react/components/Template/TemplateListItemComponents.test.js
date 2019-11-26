import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { TemplateDetails, Group } from '../../../../../src/assets/js/react/components/Template/TemplateListItemComponents'

describe('Template - TemplateListItemComponents.js', () => {

  let wrapper
  let component

  test('renders <TemplateDetails /> component and text', () => {
    wrapper = shallow(<TemplateDetails label={'Label Text'} />)
    component = findByTestAttr(wrapper, 'component-templateDetails')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('Label Text')
  })

  test('renders <Group /> component and text', () => {
    wrapper = shallow(<Group group={'Group Text'} />)
    component = findByTestAttr(wrapper, 'component-group')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('Group Text')
  })
})
