import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import Kashida from '../../../../../src/assets/js/react/components/FontManager/Kashida'

describe('FontManager - Kashida.js', () => {

  const props = {
    kashida: 80,
    onHandleKashidaChange: jest.fn(),
    tabIndex: '147'
  }
  const wrapper = shallow(<Kashida {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <Kashida /> component', () => {
      const component = findByTestAttr(wrapper, 'component-Kashida')

      expect(component.length).toBe(1)
    })

    test('render kashida input number field', () => {
      expect(wrapper.find('input').length).toBe(1)
    })
  })
})
