import React from 'react'
import { shallow } from 'enzyme'
import Empty from '../../../../../src/assets/js/react/components/Empty'

describe('<Empty />', () => {

  it('renders nothing when triggered', () => {
    const comp = shallow(<Empty />)

    expect(comp.text()).to.be.empty
  })
})
