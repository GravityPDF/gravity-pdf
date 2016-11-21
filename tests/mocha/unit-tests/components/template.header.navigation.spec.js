import React from 'react'
import { mount } from 'enzyme'
import configureStore from 'redux-mock-store'
import { Provider } from 'react-redux'
const mockStore = configureStore()
import Immutable from 'immutable'

import TemplateHeaderNavigation from '../../../../src/assets/js/react/components/TemplateHeaderNavigation'

describe('<TemplateHeaderNavigation />', () => {

  it('should render two buttons with correct classes and text', () => {
    const comp = mount(<Provider store={mockStore()}>
      <TemplateHeaderNavigation
        templates={Immutable.fromJS([{}, {}])}
        template={Immutable.fromJS({})}
        templateIndex={0}
        showPreviousTemplateText="Show previous template"
        showNextTemplateText="Show next template"
      />
    </Provider>)

    expect(comp.find('button.left')).to.have.length(1)
    expect(comp.find('button.right')).to.have.length(1)

    expect(comp.find('button.left').hasClass('dashicons-no')).to.be.true
    expect(comp.find('button.right').hasClass('dashicons-no')).to.be.true

    expect(comp.find('button.left span').text()).to.equal('Show previous template')
    expect(comp.find('button.right span').text()).to.equal('Show next template')
  })

  it('should disable left button', () => {
    const comp = mount(<Provider store={mockStore()}>
      <TemplateHeaderNavigation
        templates={Immutable.fromJS([{id: 'first-id'}, {id: 'middle-id'}, {id: 'last-id'}])}
        template={Immutable.fromJS({id: 'first-id'})}
        templateIndex={0}
      />
    </Provider>)

    expect(comp.find('button.left.disabled')).to.have.length(1)
    expect(comp.find('button.right.disabled')).to.have.length(0)

    expect(comp.render().find('button.left').attr('disabled')).to.equal('disabled')
    expect(comp.render().find('button.right').attr('disabled')).to.not.equal('disabled')
  })

  it('should disable right button', () => {
    const comp = mount(<Provider store={mockStore()}>
      <TemplateHeaderNavigation
        templates={Immutable.fromJS([{id: 'first-id'}, {id: 'middle-id'}, {id: 'last-id'}])}
        template={Immutable.fromJS({id: 'last-id'})}
        templateIndex={2}
      />
    </Provider>)

    expect(comp.find('button.left.disabled')).to.have.length(0)
    expect(comp.find('button.right.disabled')).to.have.length(1)

    expect(comp.render().find('button.left').attr('disabled')).to.not.equal('disabled')
    expect(comp.render().find('button.right').attr('disabled')).to.equal('disabled')
  })

  it('both buttons should NOT be disabled', () => {
    const comp = mount(<Provider store={mockStore()}>
      <TemplateHeaderNavigation
        templates={Immutable.fromJS([{id: 'first-id'}, {id: 'middle-id'}, {id: 'last-id'}])}
        template={Immutable.fromJS({id: 'middle-id'})}
        templateIndex={1}
      />
    </Provider>)

    expect(comp.find('button.left.disabled')).to.have.length(0)
    expect(comp.find('button.right.disabled')).to.have.length(0)

    expect(comp.render().find('button.left').attr('disabled')).to.not.equal('disabled')
    expect(comp.render().find('button.right').attr('disabled')).to.not.equal('disabled')
  })

  it('when left or right arrows pressed the route gets updated', () => {
    const comp = mount(<Provider store={mockStore()}>
      <TemplateHeaderNavigation
      templates={Immutable.fromJS([{id: 'first-id'}, {id: 'middle-id'}, {id: 'last-id'}])}
      template={Immutable.fromJS({id: 'middle-id'})}
      templateIndex={1} />
    </Provider>)

    comp.find('button.left').simulate('keydown', { key: "ArrowLeft", keyCode: 37 })
    expect(window.location.hash).to.equal('#/template/first-id')

    comp.find('button.right').simulate('keydown', { key: "ArrowRight", keyCode: 39 })
    expect(window.location.hash).to.equal('#/template/last-id')

  })

})