import React from 'react'
import { mount } from 'enzyme'
import { Provider } from 'react-redux'
import configureStore from 'redux-mock-store'
import { HashRouter as Router } from 'react-router-dom'

const mockStore = configureStore()

import { TemplateList } from '../../../../../../src/assets/js/react/components/Template/TemplateList'

describe('<TemplateList />', () => {

  it('our template container, search bar and single template item should be displayed', () => {
    const comp = mount(<Router>
      <Provider store={mockStore({template: {search: ''}})}>
        <TemplateList templates={[{id: 'my-id', compatible: true, path: ''}]}
                      route={{activateText: 'Activate'}}/>
      </Provider>
    </Router>)

    const wrapper = comp.render()

    expect(wrapper.find('.theme-backdrop')).has.length(1)
    expect(wrapper.find('input.wp-filter-search')).has.length(1)
    expect(wrapper.find('.theme')).has.length(2)
    /* one for our theme and one for our dropzone */
  })
})
