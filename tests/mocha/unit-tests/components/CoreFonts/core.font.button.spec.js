import React from 'react'
import { shallow, mount } from 'enzyme'

import CoreFontButton from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontButton'

describe('<CoreFontButton />', () => {
  it('Render a button', () => {
    const clickCallback = sinon.spy()
    const comp = shallow(<CoreFontButton className="my-class" text="Click Me!" callback={clickCallback}/>)
    expect(comp.html()).to.equal('<button class="my-class" type="button">Click Me!</button>')
  })

  it('Callback gets executed on click', () => {
    const clickCallback = sinon.spy()
    const comp = mount(<CoreFontButton className="my-class" text="Click Me!" callback={clickCallback}/>)

    expect(clickCallback.called).to.equal(false)

    const button = comp.find('button')
    button.simulate('click')

    expect(clickCallback.called).to.equal(true)
  })
})