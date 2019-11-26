import React from 'react'
import { shallow } from 'enzyme'
import Spinner from '../../../../src/assets/js/react/components/Spinner'

describe('Components - Spinner.js', () => {

  let wrapper

  test('renders <Spinner /> component', () => {
    wrapper = shallow(<Spinner />)

    expect(wrapper.find('img').length).toBe(1)
    expect(wrapper.hasClass('gfpdf-spinner')).toEqual(true)
  })
})
