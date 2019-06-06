import React from 'react'
import { mount } from 'enzyme'
import { createHashHistory } from 'history'
import { TemplateActivateButton } from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton'

let History = createHashHistory()

describe('<TemplateActivateButton />', () => {

  beforeEach(function () {
    History.replace('/template')
  })

  it('a button should be displayed', () => {
    const comp = mount(
      <TemplateActivateButton
        history={History}
        buttonText='Activate'
      />
    )

    expect(comp.find('a').text()).to.equal('Activate')
  })

  it('simulate click on a button and check our functions fire', () => {
    const onButtonClick = sinon.spy()
    const comp = mount(
      <TemplateActivateButton
        history={History}
        onTemplateSelect={onButtonClick}
        template={{}}
        activateText='Activate'
      />
    )
    comp.find('a').simulate('click')

    expect(window.location.hash).to.equal('#/')
    expect(onButtonClick.calledOnce).to.equal(true)
  })
})
