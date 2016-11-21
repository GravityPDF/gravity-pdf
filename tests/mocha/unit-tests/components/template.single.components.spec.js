import React from 'react'
import { shallow, mount, render } from 'enzyme'

import {
  CurrentTemplate,
  Name,
  Author,
  Group,
  Description,
  Tags
} from '../../../../src/assets/js/react/components/TemplateSingleComponents'

describe('<CurrentTemplate />', () => {
  it('renders empty because no isCurrentTemplate prop passed', () => {
    const comp = shallow(<CurrentTemplate label="Current Template" />)
    expect(comp.text()).to.be.empty
  })

  it('renders current template label', () => {
    const comp = shallow(<CurrentTemplate label="Current Template" isCurrentTemplate={true} />)
    expect(comp.find('.current-label').text()).to.equal('Current Template')
  })
})

describe('<Name /> and <Version /> testing', () => {
  it('renders h2 with title but no version', () => {
    const comp = mount(<Name name="Title" />)
    expect(comp.find('h2')).to.have.length(1)
    expect(comp.find('h2').hasClass('theme-name')).to.be.true
    expect(comp.text()).to.equal('Title')
  })

  it('renders h2 with title and version', () => {
    const comp = mount(<Name name="Title" version="2.0.0" versionLabel="Version" />)
    expect(comp.find('span.theme-version')).to.have.length(1)
    expect(comp.find('span.theme-version').text()).to.equal('Version: 2.0.0')
  })
})

describe('<Author />', () => {
  it('render without link', () => {
    const comp = shallow(<Author author="Creator" />)
    expect(comp.find('p')).to.have.length(1)
    expect(comp.find('p').hasClass('theme-author')).to.be.true
    expect(comp.text()).to.equal('Creator')
  })

  it('render with link', () => {
    const comp = render(<Author author="Creator" uri="http://test.com" />)
    expect(comp.find('a')).to.have.length(1)
    expect(comp.find('a').attr('href')).to.equal('http://test.com')
  })
})

describe('<Group />', () => {
  it('renders correct code', () => {
    const comp = shallow(<Group label="Group" group="Name" />)
    expect(comp.find('p').hasClass('theme-author')).to.be.true
    expect(comp.find('strong')).to.have.length(1)
    expect(comp.find('strong').text()).to.equal('Group: Name')
  })
})

describe('<Description />', () => {
  it('renders correct code', () => {
    const comp = shallow(<Description desc="Text" />)
    expect(comp.find('p').hasClass('theme-description')).to.be.true
    expect(comp.find('p').text()).to.equal('Text')
  })
})


describe('<Tags />', () => {
  it('renders empty because no tags prop passed', () => {
    const comp = shallow(<Tags label="Tags" />)
    expect(comp.text()).to.be.empty
  })

  it('renders tags', () => {
    const comp = shallow(<Tags label="Tags" tags="My tag" />)
    expect(comp.find('p').hasClass('theme-tags')).to.be.true
    expect(comp.find('p').text()).to.equal('Tags: My tag')
  })
})
