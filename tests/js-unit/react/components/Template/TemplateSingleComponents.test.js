import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import {
  CurrentTemplate,
  Name,
  Version,
  Author,
  Group,
  Description,
  Tags
} from '../../../../../src/assets/js/react/components/Template/TemplateSingleComponents'

describe('Template - TemplateSingleComponents.js', () => {

  let wrapper
  let component
  let props

  test('renders <CurrentTemplate /> component', () => {
    props = {
      isCurrentTemplate: true,
      label: 'text'
    }
    wrapper = shallow(<CurrentTemplate {...props} />)
    component = findByTestAttr(wrapper, 'component-currentTemplate')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('text')
  })

  test('renders <Name /> component', () => {
    props = {
      name: 'nameText',
      version: '4',
      versionLabel: 'versionLabelText'
    }
    wrapper = shallow(<Name {...props} />)
    component = findByTestAttr(wrapper, 'component-name')

    expect(component.length).toBe(1)
    expect(wrapper.find('Version').length).toBe(1)
  })

  test('renders <Version /> component', () => {
    props = {
      version: '4',
      label: 'labelText'
    }
    wrapper = shallow(<Version {...props} />)
    component = findByTestAttr(wrapper, 'component-version')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('labelText: 4')
  })

  test('renders <Author /> component', () => {
    props = {
      author: 'authorText'
    }
    wrapper = shallow(<Author {...props} />)
    component = findByTestAttr(wrapper, 'component-author')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('authorText')
  })

  test('renders <Author /> component with link', () => {
    props = {
      author: 'authorText',
      uri: 'uriContent'
    }
    wrapper = shallow(<Author {...props} />)
    component = findByTestAttr(wrapper, 'component-author')

    expect(component.length).toBe(1)
    expect(wrapper.find('a').length).toBe(1)
    expect(wrapper.find('a').text()).toBe('authorText')
  })

  test('renders <Group /> component', () => {
    props = {
      label: 'labelText',
      group: 'groupContent'
    }
    wrapper = shallow(<Group {...props} />)
    component = findByTestAttr(wrapper, 'component-group')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('labelText: groupContent')
  })

  test('renders <Description /> component', () => {
    props = {
      desc: 'descText'
    }
    wrapper = shallow(<Description {...props} />)
    component = findByTestAttr(wrapper, 'component-description')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('descText')
  })

  test('renders <Tags /> component', () => {
    props = {
      label: 'labelText',
      tags: 'tagsContent'
    }
    wrapper = shallow(<Tags {...props} />)
    component = findByTestAttr(wrapper, 'component-tags')

    expect(component.length).toBe(1)
    expect(component.text()).toBe('labelText: tagsContent')
  })
})
