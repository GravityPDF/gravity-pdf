import React from 'react'
import { shallow, mount } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import ConnectedTemplateUploader, { TemplateUploader, mapDispatchToProps } from '../../../../../src/assets/js/react/components/Template/TemplateUploader'

const file = (name, size = 1137334) => ({
  lastModified: 1552267520000,
  name,
  path: name,
  size,
  type: 'application/zip',
  webkitRelativePath: ''
})

describe('Template - TemplateUploader.js', () => {
  let wrapper
  let component
  const postTemplateUploadProcessingMock = jest.fn()
  const templateUploadRejectedMock = jest.fn()
  const dismissTemplateUploadSuccessMock = jest.fn()

  const uploaderProps = (props = {}) => ({
    postTemplateUploadProcessing: postTemplateUploadProcessingMock,
    templateUploadRejected: templateUploadRejectedMock,
    dismissTemplateUploadSuccess: dismissTemplateUploadSuccessMock,
    templateUploads: [],
    ...props
  })

  const setupUploader = (props = {}) => shallow(<TemplateUploader {...uploaderProps(props)} />)

  const statusMessages = (uploader, testAttr) =>
    findByTestAttr(shallow(uploader.instance().renderStatus()), testAttr)

  beforeEach(() => jest.clearAllMocks())

  describe('Check for redux properties', () => {
    const setup = (state = {}) => {
      const store = storeFactory(state)
      wrapper = shallow(<ConnectedTemplateUploader store={store} />).dive().dive()

      return wrapper
    }
    const dispatch = jest.fn()

    test('has access to the upload batch', () => {
      const templateUploads = [{ id: 1, filename: 'one.zip', status: 'pending' }]
      wrapper = setup({ template: { templateUploads } })

      expect(wrapper.instance().props.templateUploads).toEqual(templateUploads)
    })

    test('check for mapDispatchToProps postTemplateUploadProcessing()', () => {
      mapDispatchToProps(dispatch).postTemplateUploadProcessing()

      expect(dispatch.mock.calls[0][0].type).toBe('POST_TEMPLATE_UPLOAD_PROCESSING')
    })

    test('check for mapDispatchToProps templateUploadRejected()', () => {
      mapDispatchToProps(dispatch).templateUploadRejected([])

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'TEMPLATE_UPLOAD_REJECTED', payload: [] })
    })

    test('check for mapDispatchToProps dismissTemplateUploadSuccess()', () => {
      mapDispatchToProps(dispatch).dismissTemplateUploadSuccess()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'DISMISS_TEMPLATE_UPLOAD_SUCCESS' })
    })
  })

  describe('handleOndrop()', () => {
    test('uploads every zip that was dropped', () => {
      wrapper = setupUploader()
      wrapper.instance().handleOndrop([
        file('gpdf-cellulose-1.4.0.zip'),
        file('gpdf-blueprint-1.0.0.zip'),
        file('gpdf-flow-2.0.0.zip')
      ])

      expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(3)
      expect(templateUploadRejectedMock.mock.calls.length).toBe(0)
    })

    test('queues the uploads before reporting the rejected files', () => {
      const calls = []
      postTemplateUploadProcessingMock.mockImplementation(() => calls.push('upload'))
      templateUploadRejectedMock.mockImplementation(() => calls.push('rejected'))

      wrapper = setupUploader()
      wrapper.instance().handleOndrop(
        [file('gpdf-cellulose-1.4.0.zip')],
        [
          { file: file('not-a-template.txt'), errors: [{ code: 'file-invalid-type' }] },
          { file: file('huge.zip'), errors: [{ code: 'file-too-large' }] }
        ]
      )

      expect(calls).toEqual(['upload', 'rejected'])
      expect(templateUploadRejectedMock.mock.calls[0][0]).toEqual([
        { filename: 'not-a-template.txt', message: 'notZip' },
        { filename: 'huge.zip', message: 'tooBig' }
      ])

      postTemplateUploadProcessingMock.mockReset()
      templateUploadRejectedMock.mockReset()
    })

    test('ignores an empty drop', () => {
      wrapper = setupUploader()
      wrapper.instance().handleOndrop([], [])

      expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(0)
      expect(templateUploadRejectedMock.mock.calls.length).toBe(0)
    })
  })

  describe('Upload status', () => {
    test('keeps the progress notice up until every upload in the batch reports back', () => {
      wrapper = setupUploader({
        templateUploads: [
          { id: 1, filename: 'one.zip', status: 'success' },
          { id: 2, filename: 'two.zip', status: 'pending' }
        ]
      })

      expect(wrapper.instance().isUploading).toBe(true)

      wrapper.setProps({
        templateUploads: [
          { id: 1, filename: 'one.zip', status: 'success' },
          { id: 2, filename: 'two.zip', status: 'success' }
        ]
      })

      expect(wrapper.instance().isUploading).toBe(false)
    })

    test('reports success alongside the errors in the batch', () => {
      wrapper = setupUploader({
        templateUploads: [
          { id: 1, filename: 'one.zip', status: 'success' },
          { id: 2, filename: 'two.zip', status: 'failed', message: 'boom' },
          { filename: 'a.txt', status: 'failed', message: 'notZip' }
        ]
      })

      const errors = statusMessages(wrapper, 'component-stateError-showMessage')

      expect(errors.map((error) => error.prop('text'))).toEqual(['two.zip: boom', 'a.txt: notZip'])
      expect(statusMessages(wrapper, 'component-stateMessage-showMessage').length).toBe(1)
    })

    test('holds the success message until every zip has reported back', () => {
      wrapper = setupUploader({
        templateUploads: [
          { id: 1, filename: 'one.zip', status: 'success' },
          { id: 2, filename: 'two.zip', status: 'pending' }
        ]
      })

      expect(statusMessages(wrapper, 'component-stateMessage-showMessage').length).toBe(0)
    })

    test('dismisses the success message through the store', () => {
      wrapper = setupUploader({ templateUploads: [{ id: 1, filename: 'one.zip', status: 'success' }] })

      statusMessages(wrapper, 'component-stateMessage-showMessage').prop('dismissableCallback')()

      expect(dismissTemplateUploadSuccessMock.mock.calls.length).toBe(1)
    })

    test('renders nothing without a batch', () => {
      wrapper = setupUploader()

      expect(wrapper.instance().renderStatus()).toBe(null)
    })
  })

  test('renders <TemplateUploader /> component', () => {
    wrapper = setupUploader()
    component = findByTestAttr(wrapper, 'component-dropzone')

    expect(component.length).toBe(1)
  })

  test('has react-dropzone reject anything but a zip within the size limit', () => {
    wrapper = setupUploader()
    component = findByTestAttr(wrapper, 'component-dropzone')

    expect(component.prop('accept')).toEqual({ 'application/zip': ['.zip'] })
    expect(component.prop('maxSize')).toBe(1000)
  })

  test('renders the upload progress notice while a batch is in flight', () => {
    wrapper = mount(
      <TemplateUploader {...uploaderProps({ templateUploads: [{ id: 1, filename: 'one.zip', status: 'pending' }] })} />
    )

    expect(findByTestAttr(wrapper, 'component-templateUploaderStatus').text()).toContain('uploading')
  })
})
