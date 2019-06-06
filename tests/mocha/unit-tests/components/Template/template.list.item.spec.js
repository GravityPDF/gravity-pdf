import $ from 'jquery'
import React from 'react'
import { mount } from 'enzyme'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'
import { HashRouter as Router } from 'react-router-dom'
import { TemplateListItem } from '../../../../../src/assets/js/react/components/Template/TemplateListItem'

const mockStore = configureStore()

describe('<TemplateListItem />', () => {

  it('should render a template list items', () => {
    const comp = mount(<Router>
      <Provider store={mockStore()}>
        <TemplateListItem
          template={{ id: 'my-id', compatible: true }}
        />
      </Provider>
    </Router>)
    $('#karam-test-container').html(comp.html())

    expect($('div.theme').attr('data-slug')).is.equal('my-id')
    expect($('div.theme').find('.theme-screenshot')).has.length(1)
    expect($('div.theme').find('.more-details')).has.length(1)
    expect($('div.theme').find('.theme-author')).has.length(1)
    expect($('div.theme').find('h2.theme-name')).has.length(1)
    expect($('div.theme').find('.theme-actions')).has.length(1)
    expect($('div.theme').find('a.activate')).has.length(1)
  })

  it('template should be marked as active', () => {
    const comp = mount(<Router>
      <Provider store={mockStore()}>
        <TemplateListItem
          template={{ id: 'my-id' }}
          activeTemplate='my-id'
        />
      </Provider>
    </Router>)

    expect(comp.find('div.theme').hasClass('active')).is.true
  })
})
