import React from 'react'
import { shallow, mount } from 'enzyme'
import $ from 'jquery'

import ShowMessage from '../../../../src/assets/js/react/components/ShowMessage'

describe('<ShowMessage />', () => {

  let clock

  beforeEach(() => {
    clock = sinon.useFakeTimers()
  })

  afterEach(() => {
    clock.restore()
  })

  it('verify the correct html is rendered for messages', () => {
    const comp = shallow(<ShowMessage text="My Message"/>)
    $('#karam-test-container').html(comp.html())

    expect($('div.notice').length).to.equal(1)
    expect($('div.notice').find('p').length).to.equal(1)
    expect($('div.notice').text()).to.equal('My Message')
  })

  it('verify the correct html is rendered for errors', () => {
    const comp = shallow(<ShowMessage text="My Error" error={true}/>)
    $('#karam-test-container').html(comp.html())

    expect($('div.error').text()).to.equal('My Error')
  })

  it('verify the message is dismissed after a delay', () => {
    const comp = mount(<ShowMessage text="My Message" dismissable={true} delay={100}/>)

    expect(comp.find('div').render().hasClass('inline')).to.be.true
    clock.tick(101)
    expect(comp.find('div').render().hasClass('inline')).to.be.false

  })

})