import React from 'react'
import { mount } from 'enzyme'
import cheerio from 'cheerio'
import Immutable from 'immutable'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'
const mockStore = configureStore()

import { TemplateSingle } from '../../../../src/assets/js/components/TemplateSingle'

describe('<TemplateSingle />', () => {

  it('should render a single template', () => {
    const comp = mount(<Provider store={mockStore()}>
      <TemplateSingle
        templates={Immutable.fromJS([ { id: 'first-id' }, { id: 'middle-id' }, { id: 'last-id' } ])}
        template={Immutable.fromJS({ id: 'first-id' })}
        templateIndex={0}
        route={ { activateText: 'Activate' }}
      />
    </Provider>)

    const $ = cheerio.load(comp.html())
    expect($('#gfpdf-template-detail-view')).has.length(1)
    expect($('.screenshot')).has.length(1)
    expect($('h2.theme-name')).has.length(1)
    expect($('p.theme-author')).has.length(2)
    expect($('p.theme-description')).has.length(1)
    expect($('a.activate')).has.length(1)
  })
})