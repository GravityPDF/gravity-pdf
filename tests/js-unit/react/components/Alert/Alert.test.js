import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import Alert from '../../../../../src/assets/js/react/components/Alert/Alert'

describe('Alert - Alert.js', () => {

  // Mock component props
  const props = { msg: 'text' }
  const wrapper = shallow(<Alert {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <Alert /> component', () => {
      const component = findByTestAttr(wrapper, 'component-Alert')

      expect(component.length).toBe(1)
    })
  })
})
