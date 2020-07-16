import React from 'react'
import { shallow } from 'enzyme'
import Empty from '../../../../src/assets/js/react/components/Empty'

describe('Components - Empty.js', () => {

  let wrapper

  test('renders <Empty /> component', () => {
    wrapper = shallow(<Empty />)

    expect(wrapper.html()).toBe(null)
  })
})
