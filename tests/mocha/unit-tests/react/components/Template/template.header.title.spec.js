import React from 'react'
import { shallow } from 'enzyme'
import TemplateHeaderTitle from '../../../../../../src/assets/js/react/components/Template/TemplateHeaderTitle'

describe('<TemplateHeaderTitle />', () => {

  it('render a h1 tag', () => {
    const comp = shallow(<TemplateHeaderTitle />)

    expect(comp.find('h1')).to.have.length(1)
  })
})
