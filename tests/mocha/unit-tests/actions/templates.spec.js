import {
  searchTemplates,
  selectTemplate,
  addTemplate,
  updateTemplateParam,
  deleteTemplate,
  updateSelectBox,
  updateSelectBoxSuccess,
  updateSelectBoxFailed,
  templateProcessing,
  templateProcessingSuccess,
  templateProcessingFailed,
  clearTemplateProcessing,
  postTemplateUploadProcessing,
  templateUploadProcessingSuccess,
  templateUploadProcessingFailed,
  clearTemplateUploadProcessing,
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
  UPDATE_SELECT_BOX,
  UPDATE_SELECT_BOX_SUCCESS,
  UPDATE_SELECT_BOX_FAILED,
  TEMPLATE_PROCESSING,
  TEMPLATE_PROCESSING_SUCCESS,
  TEMPLATE_PROCESSING_FAILED,
  CLEAR_TEMPLATE_PROCESSING,
  POST_TEMPLATE_UPLOAD_PROCESSING,
  TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
  TEMPLATE_UPLOAD_PROCESSING_FAILED,
  CLEAR_TEMPLATE_UPLOAD_PROCESSING
} from '../../../../src/assets/js/react/actions/templates'

describe('Actions templates -', () => {

  describe('searchTemplates', () => {
    it('check it returns the correct action', () => {
      let results = searchTemplates('My Text')

      expect(results.text).is.equal('My Text')
      expect(results.type).is.equal(SEARCH_TEMPLATES)
    })
  })

  describe('selectTemplate', () => {
    it('check it returns the correct action', () => {
      let results = selectTemplate('my-id')

      expect(results.id).is.equal('my-id')
      expect(results.type).is.equal(SELECT_TEMPLATE)
    })
  })

  describe('addTemplate', () => {
    it('check it returns the correct action', () => {
      let results = addTemplate('my-id')

      expect(results.template).is.equal('my-id')
      expect(results.type).is.equal(ADD_TEMPLATE)
    })
  })

  describe('updateTemplateParam', () => {
    it('check it returns the correct action', () => {
      let results = updateTemplateParam('my-id', 'my-name', 'my-value')

      expect(results.id).is.equal('my-id')
      expect(results.name).is.equal('my-name')
      expect(results.value).is.equal('my-value')
      expect(results.type).is.equal(UPDATE_TEMPLATE_PARAM)
    })
  })

  describe('deleteTemplate', () => {
    it('check it returns the correct action', () => {
      let results = deleteTemplate('my-id')

      expect(results.id).is.equal('my-id')
      expect(results.type).is.equal(DELETE_TEMPLATE)
    })
  })

  describe('updateSelectBox', () => {
    it('check it return the correct action', () => {
      let results = updateSelectBox()

      expect(results.type).is.equal(UPDATE_SELECT_BOX)
    })
  })

  describe('updateSelectBoxSuccess', () => {
    it('check it return the correct action', () => {
      let results = updateSelectBoxSuccess('data')

      expect(results.payload).is.equal('data')
      expect(results.type).is.equal(UPDATE_SELECT_BOX_SUCCESS)
    })
  })

  describe('updateSelectBoxFailed', () => {
    it('check it return the correct action', () => {
      let results = updateSelectBoxFailed()

      expect(results.type).is.equal(UPDATE_SELECT_BOX_FAILED)
    })
  })

  describe('templateProcessing', () => {
    it('check it returns the correct action', () => {
      let results = templateProcessing('templateId')

      expect(results.payload).is.equal('templateId')
      expect(results.type).is.equal(TEMPLATE_PROCESSING)
    })
  })

  describe('templateProcessingSuccess', () => {
    it('check it returns the correct action', () => {
      let results = templateProcessingSuccess('data')

      expect(results.payload).is.equal('data')
      expect(results.type).is.equal(TEMPLATE_PROCESSING_SUCCESS)
    })
  })

  describe('templateProcessingFailed', () => {
    it('check it returns the correct action', () => {
      let results = templateProcessingFailed('data')

      expect(results.payload).is.equal('data')
      expect(results.type).is.equal(TEMPLATE_PROCESSING_FAILED)
    })
  })

  describe('clearTemplateProcessing', () => {
    it('check it returns the correct action', () => {
      let results = clearTemplateProcessing()

      expect(results.type).is.equal(CLEAR_TEMPLATE_PROCESSING)
    })
  })

  describe('postTemplateUploadProcessing', () => {
    it('check it returns the correct action', () => {
      let data = {file: {data: 'text'}, filename: 'filename'}
      let results = postTemplateUploadProcessing(data.file, data.filename)

      expect(results.payload.file).is.equal(data.file)
      expect(results.payload.filename).is.equal(data.filename)
      expect(results.type).is.equal(POST_TEMPLATE_UPLOAD_PROCESSING)
    })
  })

  describe('templateUploadProcessingSuccess', () => {
    it('check it returns the correct action', () => {
      let data = {success: {data: 'success'}}
      let results = templateUploadProcessingSuccess(data)

      expect(results.payload).is.equal(data)
      expect(results.type).is.equal(TEMPLATE_UPLOAD_PROCESSING_SUCCESS)
    })
  })

  describe('templateUploadProcessingFailed', () => {
    it('check it returns the correct action', () => {
      let error = {error: {error: 'error'}}
      let results = templateUploadProcessingFailed(error)

      expect(results.payload).is.equal(error)
      expect(results.type).is.equal(TEMPLATE_UPLOAD_PROCESSING_FAILED)
    })
  })

  describe('clearTemplateUploadProcessing', () => {
    it('check it returns the correct action', () => {
      let results = clearTemplateUploadProcessing()

      expect(results.type).is.equal(CLEAR_TEMPLATE_UPLOAD_PROCESSING)
    })
  })
})
