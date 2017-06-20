import React from 'react'
import { mount } from 'enzyme'
import createHistory from 'history/createHashHistory';

import { TemplateCloseDialog } from '../../../../src/assets/js/react/components/TemplateCloseDialog'

describe('<TemplateCloseDialog />', () => {

  let History = createHistory()

  beforeEach(function () {
    History.replace('/templates/zadani')
  })

  it('a button should be displayed', () => {
    const comp = mount(<TemplateCloseDialog history={History} />)
    const button = comp.find('button')

    expect(button).to.have.length(1)
    expect(button.hasClass('close')).to.be.true
  })

  it('when clicked it should update the route', () => {
    const comp = mount(<TemplateCloseDialog history={History} />)
    comp.find('button').simulate('click')
    expect(window.location.hash).to.equal('#/')
  })

  it('when esc button pressed it should update the route', () => {
    const comp = mount(<TemplateCloseDialog history={History} />)
    comp.simulate('keydown', { key: "Escape", keyCode: 27 })
    expect(window.location.hash).to.equal('#/')
  })

  it('it should redirect to a route passed by props', () => {
    const comp = mount(<TemplateCloseDialog history={History} closeRoute="/template" />)
    comp.simulate('keydown', { key: "Escape", keyCode: 27 })
    expect(window.location.hash).to.equal('#/template')
  })
})