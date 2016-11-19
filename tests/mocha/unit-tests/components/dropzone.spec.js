import React from 'react'
import { shallow } from 'enzyme'
import $ from 'jquery'

import Dropzone from '../../../../src/assets/js/react/components/Dropzone'

describe('<Dropzone />', () => {
  it('verify the appropriate markup is rendered when using dropzone', () => {
    const comp = shallow(<Dropzone onDrop={() => {}}>My Content</Dropzone>)

    $('#karam-test-container').html(comp.html())

    expect($('.gfpdf-dropzone').length).to.equal(1)
    expect($('.gfpdf-dropzone').find('input:file').length).to.equal(1)
  })
})