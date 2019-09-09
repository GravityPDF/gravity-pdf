import React from 'react'
import { shallow } from 'enzyme'
import Spinner from '../../../../../src/assets/js/react/components/Spinner'

describe('<Spinner />', () => {

  it('Render spinner image', () => {
    let comp = shallow(<Spinner />)

    expect(comp.html()).to.equal('<img class="gfpdf-spinner"/>')

    GFPDF.spinnerAlt = 'Loading'
    GFPDF.spinnerUrl = 'spinner.png'
    comp = shallow(<Spinner />)

    expect(comp.html()).to.equal('<img alt="Loading" src="spinner.png" class="gfpdf-spinner"/>')
  })
})
