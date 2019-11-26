import React from 'react'
import { shallow, mount } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import ConnectedTemplateUploader, { TemplateUploader } from '../../../../../src/assets/js/react/components/Template/TemplateUploader'
import { mapDispatchToProps } from '../../../../../src/assets/js/react/components/Template/TemplateUploader'

describe('Template - TemplateUploader.js', () => {

  let wrapper
  let component
  const postTemplateUploadProcessingMock = jest.fn()
  const addNewTemplateMock = jest.fn()
  const clearTemplateUploadProcessingMock = jest.fn()
  const updateTemplateParamMock = jest.fn()

  describe('Check for redux properties', () => {

    const setup = (state = {}) => {
      const store = storeFactory(state)
      wrapper = shallow(<ConnectedTemplateUploader store={store} />).dive().dive()

      return wrapper
    }
    const dispatch = jest.fn()

    setup()

    test('has access to `list` state', () => {
      wrapper = setup()
      const templatesProp = wrapper.instance().props.templates

      expect(templatesProp).toBeInstanceOf(Array)
    })

    test('has access to `templateUploadProcessingSuccess` state', () => {
      wrapper = setup({ template: { templateUploadProcessingSuccess: { test: 'test' } } })
      const templateUploadProcessingSuccessProp = wrapper.instance().props.templateUploadProcessingSuccess

      expect(templateUploadProcessingSuccessProp).toBeInstanceOf(Object)
      expect(templateUploadProcessingSuccessProp).toEqual({ test: 'test' })
    })

    test('has access to `templateUploadProcessingError` state', () => {
      wrapper = setup({ template: { templateUploadProcessingError: { error: 'test' } } })
      const templateUploadProcessingErrorProp = wrapper.instance().props.templateUploadProcessingError

      expect(templateUploadProcessingErrorProp).toBeInstanceOf(Object)
      expect(templateUploadProcessingErrorProp).toEqual({ error: 'test' })
    })

    test('check for mapDispatchToProps addNewTemplate()', () => {
      mapDispatchToProps(dispatch).addNewTemplate()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'ADD_TEMPLATE' })
    })

    test('check for mapDispatchToProps updateTemplateParam()', () => {
      mapDispatchToProps(dispatch).updateTemplateParam()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'UPDATE_TEMPLATE_PARAM' })
    })

    test('check for mapDispatchToProps postTemplateUploadProcessing()', () => {
      mapDispatchToProps(dispatch).postTemplateUploadProcessing()

      expect(dispatch.mock.calls[0][0].type).toBe('POST_TEMPLATE_UPLOAD_PROCESSING')
    })

    test('check for mapDispatchToProps clearTemplateUploadProcessing()', () => {
      mapDispatchToProps(dispatch).clearTemplateUploadProcessing()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'CLEAR_TEMPLATE_UPLOAD_PROCESSING' })
    })
  })

  describe('Component functions', () => {

    test('handleOndrop() - Manages the template file upload', () => {
      const acceptedFiles = [
        {
          lastModified: 1552267520000,
          name: 'gpdf-cellulose-1.4.0.zip',
          path: 'gpdf-cellulose-1.4.0.zip',
          size: 1137334,
          type: 'application/zip',
          webkitRelativePath: ''
        }
      ]

      wrapper = shallow(
        <TemplateUploader postTemplateUploadProcessing={postTemplateUploadProcessingMock} />
      )
      wrapper.instance().handleOndrop(acceptedFiles)

      expect(wrapper.state('ajax')).toBe(true)
      expect(wrapper.state('error')).toBe('')
      expect(wrapper.state('message')).toBe('')
      expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(1)
    })

    test('checkFilename() - Checks if the uploaded file has a .zip extension', () => {
      let name
      name = 'gpdf-cellulose-1.4.0.zip'

      wrapper = shallow(<TemplateUploader filenameErrorText='errorText' />)

      expect(wrapper.instance().checkFilename(name)).toBe(true)

      name = 'gpdf-cellulose-1.4.0'

      wrapper.instance().checkFilename(name)

      expect(wrapper.state('error')).toBe('errorText')
      expect(wrapper.instance().checkFilename(name)).toBe(false)
    })

    test('checkFilesize() - Checks if the file size is larger than 5MB', () => {
      let size
      size = 1137334

      wrapper = shallow(<TemplateUploader filesizeErrorText='errorText' />)

      expect(wrapper.instance().checkFilesize(size)).toBe(true)

      size = 999999999

      wrapper.instance().checkFilesize(size)
      expect(wrapper.state('error')).toBe('errorText')
      expect(wrapper.instance().checkFilesize(size)).toBe(false)
    })

    test('ajaxSuccess() - Update our Redux store with the new PDF template details', () => {
      let response
      const templates = [
        { template: 'Blank Slate', id: 'blank-slate' },
        { template: 'Focus Gravity', id: 'focus-gravity' },
        { template: 'Rubix', id: 'rubix' },
        { template: 'Zadani', id: 'zadani' }
      ]
      response = { body: { templates: [{ template: 'Cellulose', id: 'gpdf-cellulose' }] } }

      wrapper = shallow(
        <TemplateUploader
          templates={templates}
          addNewTemplate={addNewTemplateMock}
          clearTemplateUploadProcessing={clearTemplateUploadProcessingMock}
          templateSuccessfullyInstalledUpdated='successText' />
      )
      wrapper.instance().ajaxSuccess(response)

      expect(addNewTemplateMock.mock.calls.length).toBe(1)
      expect(wrapper.state('ajax')).toBe(false)
      expect(wrapper.state('message')).toBe('successText')
      expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(1)

      response = { body: { templates: [{ template: 'Rubix', id: 'rubix' }] } }

      wrapper = shallow(
        <TemplateUploader
          templates={templates}
          updateTemplateParam={updateTemplateParamMock}
          clearTemplateUploadProcessing={clearTemplateUploadProcessingMock}
          templateSuccessfullyInstalledUpdated='successText' />
      )
      wrapper.instance().ajaxSuccess(response)

      expect(addNewTemplateMock.mock.calls.length).toBe(1)
      expect(wrapper.state('ajax')).toBe(false)
      expect(wrapper.state('message')).toBe('successText')
      expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(2)
    })

    test('ajaxFailed() - Show any errors to the user when AJAX request fails for any reason', () => {
      let error
      error = {
        response: {
          body: {
            error: 'error'
          }
        }
      }

      wrapper = shallow(
        <TemplateUploader clearTemplateUploadProcessing={clearTemplateUploadProcessingMock} />
      )
      wrapper.instance().ajaxFailed(error)

      expect(wrapper.state('error')).toBe('error')
      expect(wrapper.state('ajax')).toBe(false)
      expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(1)

      error = {
        response: {
          body: {}
        }
      }

      wrapper = shallow(
        <TemplateUploader
          clearTemplateUploadProcessing={clearTemplateUploadProcessingMock}
          genericUploadErrorText={'errorText'} />
      )
      wrapper.instance().ajaxFailed(error)

      expect(wrapper.state('error')).toBe('errorText')
      expect(wrapper.state('ajax')).toBe(false)
      expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(2)
    })

    test('removeMessage() - Remove message from state once the timeout has finished', () => {
      wrapper = shallow(<TemplateUploader />)
      wrapper.instance().removeMessage()

      expect(wrapper.state('message')).toBe('')
    })
  })

  describe('Run Lifecycle methods', () => {

    test('componentDidUpdate() - Fires appropriate function based on Redux store data (success)', () => {
      const props = {
        templateUploadProcessingSuccess: {
          statusCode: 200,
          status: 200,
          body: {
            templates: [{
              template: 'Cellulose',
              id: 'gpdf-cellulose'
            }]
          }
        },
        templateUploadProcessingError: {}
      }
      const prevProps = {
        templateUploadProcessingSuccess: {},
        templateUploadProcessingError: {}
      }
      const templates = [
        { template: 'Blank Slate', id: 'blank-slate' },
        { template: 'Focus Gravity', id: 'focus-gravity' },
        { template: 'Rubix', id: 'rubix' },
        { template: 'Zadani', id: 'zadani' }
      ]
      wrapper = shallow(
        <TemplateUploader
          templates={templates}
          addNewTemplate={addNewTemplateMock}
          clearTemplateUploadProcessing={clearTemplateUploadProcessingMock}
          {...props}
        />
      )
      const ajaxSuccess = jest.spyOn(wrapper.instance(), 'ajaxSuccess')
      wrapper.instance().componentDidUpdate(prevProps)

      expect(ajaxSuccess).toHaveBeenCalledTimes(1)
    })

    test('componentDidUpdate() - Fires appropriate function based on Redux store data (error)', () => {
      const props = {
        templateUploadProcessingSuccess: {},
        templateUploadProcessingError: {
          response: {
            body: {
              error: 'error'
            }
          }
        }
      }
      const prevProps = {
        templateUploadProcessingSuccess: {},
        templateUploadProcessingError: {}
      }
      wrapper = shallow(
        <TemplateUploader
          clearTemplateUploadProcessing={clearTemplateUploadProcessingMock}
          {...props}
        />
      )
      const ajaxFailed = jest.spyOn(wrapper.instance(), 'ajaxFailed')
      wrapper.instance().componentDidUpdate(prevProps)

      expect(ajaxFailed).toHaveBeenCalledTimes(1)
    })
  })

  test('renders <TemplateUploader /> component', () => {
    wrapper = shallow(<TemplateUploader />)
    component = findByTestAttr(wrapper, 'component-templateUploader')

    expect(component.length).toBe(1)
  })

  test('renders <Dropzone /> component', () => {
    wrapper = shallow(<TemplateUploader />)
    component = findByTestAttr(wrapper, 'component-dropzone')

    expect(component.length).toBe(1)
  })

  test(`renders <ShowMessage /> component if state.error !== ''`, () => {
    wrapper = mount(<TemplateUploader />)
    wrapper.setState({ error: 'errorText' })
    component = findByTestAttr(wrapper, 'component-stateError-showMessage')

    expect(component.length).toBe(1)
  })

  test(`renders <ShowMessage /> component if state.error !== ''`, () => {
    wrapper = mount(<TemplateUploader />)
    wrapper.setState({ message: 'errorText' })
    component = findByTestAttr(wrapper, 'component-stateMessage-showMessage')

    expect(component.length).toBe(1)
  })
})
