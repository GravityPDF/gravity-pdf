import React from 'react'
import { mount } from 'enzyme'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'

const mockStore = configureStore()

import { TemplateDeleteButton as DeleteButton } from '../../../../../src/assets/js/react/components/Template/TemplateDeleteButton'

describe('<DeleteButton />', () => {

  it('verify the correct html is rendered', () => {
    const comp = mount(<Provider store={mockStore()}>
      <DeleteButton buttonText="Delete"/>
    </Provider>)

    expect(comp.find('a').hasClass('delete-theme')).to.be.true
    expect(comp.find('a').text()).to.equal('Delete')
  })

  it('verify the callback function is called', () => {
    const onButtonClick = sinon.spy()
    const comp = mount(<Provider store={mockStore()}>
      <DeleteButton buttonText="Delete" callbackFunction={onButtonClick}/>
    </Provider>)

    comp.find('a').simulate('click')
    expect(onButtonClick.calledOnce).to.equal(true)
  })

})
