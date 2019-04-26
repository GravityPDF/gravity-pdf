import React from 'react'
import { shallow } from 'enzyme'

import {
  TemplateDetails,
  Group
} from '../../../../../../src/assets/js/react/components/Template/TemplateListItemComponents'

describe('<TemplateDetails />', () => {
  it('renders span', () => {
    const comp = shallow(<TemplateDetails label="Template Details"/>)
    expect(comp.find('span.more-details')).to.have.length(1)
    expect(comp.text()).to.equal('Template Details')
  })
})

describe('<Group />', () => {
  it('renders p tag with props', () => {
    const comp = shallow(<Group group="My Group"/>)
    expect(comp.find('p.theme-author')).to.have.length(1)
    expect(comp.text()).to.equal('My Group')
  })
})
