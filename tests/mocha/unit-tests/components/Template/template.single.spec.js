import React from 'react'
import { mount } from 'enzyme'
import $ from 'jquery'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'

const mockStore = configureStore()
import { HashRouter as Router } from 'react-router-dom'

import { TemplateSingle } from '../../../../../src/assets/js/react/components/Template/TemplateSingle'

describe('<TemplateSingle />', () => {

  it('should render a single template', () => {
    const comp = mount(<Router>
      <Provider store={mockStore()}>
        <TemplateSingle
          templates={[{id: 'first-id', compatible: true, path: ''}, {
            id: 'middle-id',
            compatible: true,
            path: ''
          }, {id: 'last-id', compatible: true, path: ''}]}
          template={{id: 'first-id', compatible: true, path: ''}}
          templateIndex={0}
          route={{activateText: 'Activate'}}
        />
      </Provider>
    </Router>)

    $('#karam-test-container').html(comp.html())

    expect($('#gfpdf-template-detail-view')).has.length(1)
    expect($('.screenshot')).has.length(1)
    expect($('h2.theme-name')).has.length(1)
    expect($('p.theme-author')).has.length(2)
    expect($('p.theme-description')).has.length(1)
    expect($('a.activate')).has.length(1)
  })
})
