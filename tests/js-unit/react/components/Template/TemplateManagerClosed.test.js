import React from 'react'
import { mount } from 'enzyme'
import { Provider } from 'react-redux'
import configureStore from 'redux-mock-store'
import TemplateManagerClosed from '../../../../../src/assets/js/react/components/Template/TemplateManagerClosed'

describe('Template - TemplateManagerClosed.js', () => {
  test('clears the finished uploads when the Template Manager closes', () => {
    const store = configureStore()({})

    const wrapper = mount(
      <Provider store={store}>
        <TemplateManagerClosed />
      </Provider>
    )

    expect(store.getActions()).toEqual([{ type: 'CLEAR_FINISHED_TEMPLATE_UPLOADS' }])
    expect(wrapper.html()).toBe('')
  })
})
