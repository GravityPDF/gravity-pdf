import React from 'react'
import { mount } from 'enzyme'
import $ from 'jquery'
import Immutable from 'immutable'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'
const mockStore = configureStore()

import { TemplateListItem } from '../../../../src/assets/js/react/components/TemplateListItem'

describe('<TemplateListItem />', () => {

  it('should render a template list items', () => {

    const comp = mount(<Provider store={mockStore()}>
      <TemplateListItem
        template={Immutable.fromJS({ id: 'my-id', compatible: true })}
      />
    </Provider>)

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
    const comp = mount(<Provider store={mockStore()}>
      <TemplateListItem
        template={Immutable.fromJS({ id: 'my-id' })}
        activeTemplate="my-id"
      />
    </Provider>)

    expect(comp.find('div.theme').hasClass('active')).is.true
  })

})