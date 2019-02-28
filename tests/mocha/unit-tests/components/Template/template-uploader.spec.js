import React from 'react'
import { mount } from 'enzyme'

import { TemplateUploader } from '../../../../../src/assets/js/react/components/Template/TemplateUploader'

describe('<TemplateUploader />', () => {

  it('verify the correct html is rendered', () => {
    const comp = mount(<TemplateUploader addTemplateText="Uploading"/>)

    expect(comp.find('.gfpdf-dropzone')).to.have.length(1)
    expect(comp.find('.gfpdf-dropzone a')).to.have.length(1)
    expect(comp.find('.gfpdf-dropzone h2').text()).to.equal('Uploading')
  })
})
