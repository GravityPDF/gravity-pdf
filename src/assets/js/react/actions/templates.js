/* Redux Action Type Constants */
export const SEARCH_TEMPLATES = 'SEARCH_TEMPLATES'
export const SELECT_TEMPLATE = 'SELECT_TEMPLATE'
export const ADD_TEMPLATE = 'ADD_TEMPLATE'
export const UPDATE_TEMPLATE_PARAM = 'UPDATE_TEMPLATE_PARAM'
export const DELETE_TEMPLATE = 'DELETE_TEMPLATE'
export const UPDATE_SELECT_BOX = 'UPDATE_SELECT_BOX'
export const UPDATE_SELECT_BOX_SUCCESS = 'UPDATE_SELECT_BOX_SUCCESS'
export const UPDATE_SELECT_BOX_FAILED = 'UPDATE_SELECT_BOX_FAILED'
export const TEMPLATE_PROCESSING = 'TEMPLATE_PROCESSING'
export const TEMPLATE_PROCESSING_SUCCESS = 'TEMPLATE_PROCESSING_SUCCESS'
export const TEMPLATE_PROCESSING_FAILED = 'TEMPLATE_PROCESSING_FAILED'
export const CLEAR_TEMPLATE_PROCESSING = 'CLEAR_TEMPLATE_PROCESSING'
export const POST_TEMPLATE_UPLOAD_PROCESSING = 'POST_TEMPLATE_UPLOAD_PROCESSING'
export const TEMPLATE_UPLOAD_PROCESSING_SUCCESS = 'TEMPLATE_UPLOAD_PROCESSING_SUCCESS'
export const TEMPLATE_UPLOAD_PROCESSING_FAILED = 'TEMPLATE_UPLOAD_PROCESSING_FAILED'
export const CLEAR_TEMPLATE_UPLOAD_PROCESSING = 'CLEAR_TEMPLATE_UPLOAD_PROCESSING'

/**
 * Redux Actions - payloads of information that send data from your application to your store
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * Fires the Advanced Template Search action
 *
 * @param {string} text
 *
 * @returns {{type, text: *}}
 *
 * @since 4.1
 */
export const searchTemplates = text => {
  return {
    type: SEARCH_TEMPLATES,
    text
  }
}

/**
 * Fires the Advanced Template select/activate action
 *
 * @param {string} id The template ID
 *
 * @returns {{type, id: *}}
 *
 * @since 4.1
 */
export const selectTemplate = id => {
  return {
    type: SELECT_TEMPLATE,
    id
  }
}

/**
 * Fires the Advanced Template add new template action
 *
 * @param {object} template
 *
 * @returns {{type, template: *}}
 *
 * @since 4.1
 */
export const addTemplate = template => {
  return {
    type: ADD_TEMPLATE,
    template
  }
}

/**
 * Fires the Advanced Template update action which replaces a template parameter with a new value
 *
 * @param {string} id The template ID
 * @param {string} name The parameter key to update
 * @param {string} value The new value for the parameter
 *
 * @returns {{type, id: *, name: *, value: *}}
 *
 * @since 4.1
 */
export const updateTemplateParam = (id, name, value) => {
  return {
    type: UPDATE_TEMPLATE_PARAM,
    id,
    name,
    value
  }
}

/**
 * Fires the Advanced Template delete action which removes the template from our store
 *
 * @param  {string} id The template ID
 *
 * @returns {{type, id: *}}
 *
 * @since 4.1
 */
export const deleteTemplate = id => {
  return {
    type: DELETE_TEMPLATE,
    id
  }
}

/**
 * Fires the Update Select Box action which request the new Select Box DOM data
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const updateSelectBox = () => {
  return {
    type: UPDATE_SELECT_BOX
  }
}

/**
 * Fires the Update Select Box Success action with The new Select Box DOM data
 *
 * @param  {string} text The new Select Box DOM data
 *
 * @returns {{type, payload: text}}
 *
 * @since 5.2
 */
export const updateSelectBoxSuccess = text => {
  return {
    type: UPDATE_SELECT_BOX_SUCCESS,
    payload: text
  }
}

/**
 * Fires the Update Select Box Failed action
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const updateSelectBoxFailed = () => {
  return {
    type: UPDATE_SELECT_BOX_FAILED
  }
}

/**
 * Fires to post PDF template to our endpoint for processing
 *
 * @param  {string} templateId
 *
 * @returns {{type, payload: templateId}}
 *
 * @since 5.2
 */
export const templateProcessing = templateId => {
  return {
    type: TEMPLATE_PROCESSING,
    payload: templateId
  }
}

/**
 * Fires to get PDF processing result to our endpoint
 *
 * @param  {string} data
 *
 * @returns {{type, payload: data}}
 *
 * @since 5.2
 */
export const templateProcessingSuccess = data => {
  return {
    type: TEMPLATE_PROCESSING_SUCCESS,
    payload: data
  }
}

/**
 * Fires if an error occured during request of PDF processing to our endpoint
 *
 * @param  data
 * @returns {{type, payload: data}}
 *
 * @since 5.2
 */
export const templateProcessingFailed = data => {
  return {
    type: TEMPLATE_PROCESSING_FAILED,
    payload: data
  }
}

/**
 * Fires to clear/reset Template Processing data/result
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const clearTemplateProcessing = () => {
  return {
    type: CLEAR_TEMPLATE_PROCESSING
  }
}

/**
 * Fires request template to our endpoint for processing
 *
 * @param  {Object} file
 * @param  {String} filename
 *
 * @returns {{type, { payload: { file: file, filename: filename } }}}
 *
 * @since 5.2
 */
export const postTemplateUploadProcessing = (file, filename) => {
  return {
    type: POST_TEMPLATE_UPLOAD_PROCESSING,
    payload: {
      file,
      filename
    }
  }
}

/**
 * Fires request template to our endpoint for processing
 *
 * @param  {Object} response
 *
 * @returns {{type, payload: responseText}}
 *
 * @since 5.2
 */
export const templateUploadProcessingSuccess = response => {
  return {
    type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
    payload: response
  }
}

/**
 * Fires Update/Show error
 *
 * @param  {Object} error
 *
 * @returns {{type, payload: error}}
 *
 * @since 5.2
 */
export const templateUploadProcessingFailed = error => {
  return {
    type: TEMPLATE_UPLOAD_PROCESSING_FAILED,
    payload: error
  }
}

/**
 * Fires to clear/reset data for templateUploadProcessingSuccess and templateUploadProcessingFailed
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const clearTemplateUploadProcessing = () => {
  return {
    type: CLEAR_TEMPLATE_UPLOAD_PROCESSING
  }
}
