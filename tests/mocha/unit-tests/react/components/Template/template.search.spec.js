import React from 'react'
import { mount, render } from 'enzyme'
import { TemplateSearch } from '../../../../../../src/assets/js/react/components/Template/TemplateSearch'

let clock

describe('<TemplateSearch />', () => {

  beforeEach(() => {
    clock = sinon.useFakeTimers()
  })

  afterEach(() => {
    clock.restore()
  })

  it('correctly renders our search bar', () => {
    const searchCallback = sinon.spy()
    const comp = render(<TemplateSearch search='Initial Search' onSearch={searchCallback} />)
    const input = comp.find('input')

    expect(input.hasClass('wp-filter-search')).is.true
    expect(input.attr('type')).to.equal('search')
    expect(input.attr('value')).to.equal('Initial Search')
  })

  it('callback gets executed 200ms after search bar changes', () => {
    const searchCallback = sinon.spy()
    const comp = mount(<TemplateSearch onSearch={searchCallback} />)
    const input = comp.find('input')
    input.value = 'new value'
    input.simulate('change')
    clock.tick(199)

    expect(searchCallback.called).to.equal(false)

    clock.tick(201)

    expect(searchCallback.called).to.equal(true)
  })

})
