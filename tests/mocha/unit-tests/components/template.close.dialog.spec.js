import React from 'react'
import { mount } from 'enzyme'
import { hashHistory } from 'react-router'

import TemplateCloseDialog from '../../../../src/assets/js/components/TemplateCloseDialog'

describe('<TemplateCloseDialog />', () => {

  beforeEach(function () {
    hashHistory.push('/templates/zadani')
  })

  it('a button should be displayed', () => {
    const comp = mount(<TemplateCloseDialog />)
    const button = comp.find('button')

    expect(button).to.have.length(1)
    expect(button.hasClass('close')).to.be.true
  })

  it('when clicked it should update the route', () => {
    const comp = mount(<TemplateCloseDialog />)
    comp.find('button').simulate('click')
    expect(window.location.hash).to.equal('#/')
  })

  it('when esc button pressed it should update the route', () => {
    const comp = mount(<TemplateCloseDialog />)
    comp.simulate('keydown', { key: "Escape", keyCode: 27 })
    expect(window.location.hash).to.equal('#/')
  })

  it('it should redirect to a route passed by props', () => {
    const comp = mount(<TemplateCloseDialog closeRoute="/template" />)
    comp.simulate('keydown', { key: "Escape", keyCode: 27 })
    expect(window.location.hash).to.equal('#/template')
  })
})