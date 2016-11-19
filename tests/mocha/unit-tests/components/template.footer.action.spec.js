import React from 'react'
import { mount } from 'enzyme'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'
import Immutable from 'immutable'

const mockStore = configureStore()

import TemplateFooterActions from '../../../../src/assets/js/react/components/TemplateFooterActions'

describe('<TemplateFooterActions />', () => {

  it('should render a button', () => {


    const comp = mount(<Provider store={mockStore()}><TemplateFooterActions template={Immutable.fromJS({ path: '/my/test/path', compatible: true })} /></Provider>)

    expect(comp.find('div.theme-actions')).to.have.length(1)
    expect(comp.find('a.button')).to.have.length(1)
  })

})