import React from 'react'
import { render, mount } from 'enzyme'
import { hashHistory } from 'react-router'
import Immutable from 'immutable'

import { TemplateActivateButton } from '../../../../src/assets/js/react/components/TemplateActivateButton'

describe('<TemplateActivateButton />', () => {

  beforeEach(function () {
    hashHistory.push('/templates')
  })

  it('a button should be displayed', () => {
    const comp = render(<TemplateActivateButton buttonText="Activate" />)
    expect(comp.find('a').text()).to.equal('Activate')
  })

  it('simulate click on a button and check our functions fire', () => {
    const onButtonClick = sinon.spy()
    const comp = mount(<TemplateActivateButton onTemplateSelect={onButtonClick} template={Immutable.fromJS([])} activateText="Activate" />)

    comp.find('a').simulate('click')
    expect(window.location.hash).to.equal('#/')
    expect(onButtonClick.calledOnce).to.equal(true)
  })
})