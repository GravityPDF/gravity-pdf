import React from 'react'
import { mount } from 'enzyme'
import Immutable from 'immutable'
import { Provider } from 'react-redux'
import configureStore from 'redux-mock-store'

const mockStore = configureStore()

import { TemplateList } from '../../../../src/assets/js/react/components/TemplateList'

describe('<TemplateList />', () => {

  it('our template container, search bar and single template item should be displayed', () => {
    const comp = mount(<Provider store={mockStore( {template: { search: '' }})}>
      <TemplateList templates={Immutable.fromJS([ { id: 'my-id', compatible: true, path: '' } ])} route={ { activateText: 'Activate' }}/>
    </Provider>)

    const wrapper = comp.render()

    expect(wrapper.find('.theme-backdrop')).has.length(1)
    expect(wrapper.find('input.wp-filter-search')).has.length(1)
    expect(wrapper.find('.theme')).has.length(2) /* one for our theme and one for our dropzone */
  })
})