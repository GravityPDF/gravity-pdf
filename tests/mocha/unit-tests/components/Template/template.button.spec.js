import React from 'react'
import { shallow, mount } from 'enzyme'
import createHistory from 'history/createHashHistory';

import TemplateButton from '../../../../../src/assets/js/react/components/Template/TemplateButton'

describe('<TemplateButton />', () => {

  let History = createHistory()

  beforeEach(function () {
    History.replace('/')
  })

  it('a button should be displayed', () => {
    const comp = shallow(<TemplateButton history={History} />)
    const button = comp.find('button')

    expect(button).to.have.length(1)
    expect(button.hasClass('gfpdf-button')).to.be.true
  })

  it('url should be updated when button clicked', () => {
    const comp = mount(<TemplateButton history={History} />)

    /* Click the button and run our test */
    comp.find('button').simulate('click')
    expect(window.location.hash).to.equal('#/template')
  })
})