import React from 'react'
import { shallow } from 'enzyme'

import CoreFontCounter from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontCounter'

describe('<CoreFontCounter />', () => {
  it('Render the counter', () => {
    const comp = shallow(<CoreFontCounter text="Prefix: " queue="1"/>)

    expect(comp.text()).to.equal('Prefix: 1')
  })
})