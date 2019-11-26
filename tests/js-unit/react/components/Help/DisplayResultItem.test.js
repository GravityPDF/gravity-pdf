import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import DisplayResultItem from '../../../../../src/assets/js/react/components/Help/DisplayResultItem'

describe('Help - DisplayResultItem.js', () => {

  const props = {
    id: 0,
    link: 'https://gravitypdf.com/documentation/v5/user-global-settings/',
    title: { rendered: 'Global Settings' },
    excerpt: { rendered: '<p>Gravity PDF is fully integrated into Gravity Forms. The PDF settings are located in a separate section in Gravity Forms own settings area. You can find it by navigating to&#8230;</p>' }
  }
  const wrapper = shallow(<DisplayResultItem item={props} />)
  const component = findByTestAttr(wrapper, 'component-result-item')

  test('renders <DisplayResultItem /> component container', () => {
    expect(component.length).toBe(1)
  })

  test('displays an individual result', () => {
    expect(component.find('a[href="https://gravitypdf.com/documentation/v5/user-global-settings/"]').length).toBe(1)
    expect(component.contains(<div dangerouslySetInnerHTML={{ __html: 'Global Settings' }} />)).toBe(true)
    expect(component.contains(<div dangerouslySetInnerHTML={{ __html: '<p>Gravity PDF is fully integrated into Gravity Forms. The PDF settings are located in a separate section in Gravity Forms own settings area. You can find it by navigating to&#8230;</p>' }} />)).toBe(true)
  })
})
