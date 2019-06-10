import React from 'react'
import { shallow } from 'enzyme'

import CoreFontCounter from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontCounter'

describe('<CoreFontCounter />', () => {
  it('Render the counter', () => {
    let queue = 1
    let text = 'Prefix:'
    const comp = shallow(<CoreFontCounter text={text} queue={queue} />)

    expect(comp.text()).to.equal('Prefix: 1')
  })
})
