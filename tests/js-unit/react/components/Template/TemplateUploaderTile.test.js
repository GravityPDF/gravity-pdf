import React from 'react'
import { mount } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import TemplateUploaderTile from '../../../../../src/assets/js/react/components/Template/TemplateUploaderTile'
import { TemplateUploaderContext } from '../../../../../src/assets/js/react/components/Template/TemplateUploaderContext'

describe('Template - TemplateUploaderTile.js', () => {
  const tileLink = (wrapper) => wrapper.find('a[href="#/template"]')

  const setup = (context = {}, disabled = false) => mount(
    <TemplateUploaderContext.Provider value={{ open: jest.fn(), isUploading: false, ...context }}>
      <TemplateUploaderTile addTemplateText='Add New Template' templateInstallInstructions='instructions' disabled={disabled} />
    </TemplateUploaderContext.Provider>
  )

  test('renders the tile with its text', () => {
    const wrapper = setup()

    expect(findByTestAttr(wrapper, 'component-templateUploaderTile').length).toBe(1)
    expect(wrapper.find('h2.theme-name').text()).toBe('Add New Template')
    expect(wrapper.find('.gfpdf-template-install-instructions').text()).toBe('instructions docsLink')
    expect(findByTestAttr(wrapper, 'component-templateDocsLink').prop('href')).toBe('https://docs.gravitypdf.com/developers/start-customising/')
  })

  test('opens the file picker owned by <TemplateUploader /> instead of following the link', () => {
    const open = jest.fn()
    const preventDefault = jest.fn()
    const wrapper = setup({ open })

    tileLink(wrapper).simulate('click', { preventDefault })

    expect(open).toHaveBeenCalledTimes(1)
    expect(preventDefault).toHaveBeenCalledTimes(1)
  })

  test('does nothing when disabled', () => {
    const open = jest.fn()
    const wrapper = setup({ open }, true)
    const link = tileLink(wrapper)

    link.simulate('click')

    expect(open).not.toHaveBeenCalled()
    expect(link.prop('aria-disabled')).toBe(true)
    expect(findByTestAttr(wrapper, 'component-templateUploaderTile').hasClass('disabled')).toBe(true)
  })

  test('shows the spinner while an upload is in flight', () => {
    expect(tileLink(setup({ isUploading: true })).hasClass('doing-ajax')).toBe(true)
    expect(tileLink(setup()).hasClass('doing-ajax')).toBe(false)
  })
})
