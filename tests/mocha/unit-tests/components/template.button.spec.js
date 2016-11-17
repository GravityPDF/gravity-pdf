import React from 'react'
import { shallow, mount } from 'enzyme'
import { hashHistory } from 'react-router'

import TemplateButton from '../../../../src/assets/js/components/TemplateButton'

describe('<TemplateButton />', () => {

  before(function () {
    hashHistory.push('/')
  })

  it('a button should be displayed', () => {
    const comp = shallow(<TemplateButton />)
    const button = comp.find('button')

    expect(button).to.have.length(1)
    expect(button.hasClass('gfpdf-button')).to.be.true
  })

  it('url should be updated when button clicked', () => {
    const comp = mount(<TemplateButton />)

    /* Append our button to the DOM and manually add focus so we can test our click event */
    document.body.appendChild(comp.find('button').at(0).node)
    const button = document.getElementById('fancy-template-selector')
    button.focus()

    /* Click the button and run our test */
    comp.find('button').simulate('click')
    expect(window.location.hash).to.equal('#/template')

    /* Remove button from DOM */
    button.remove()
  })
})