/* Redux action types */
import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
  UPDATE_SELECT_BOX_SUCCESS,
  TEMPLATE_PROCESSING_SUCCESS,
  TEMPLATE_PROCESSING_FAILED,
  CLEAR_TEMPLATE_PROCESSING,
  POST_TEMPLATE_UPLOAD_PROCESSING,
  TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
  TEMPLATE_UPLOAD_PROCESSING_FAILED,
  TEMPLATE_UPLOAD_REJECTED,
  DISMISS_TEMPLATE_UPLOAD_SUCCESS,
  CLEAR_FINISHED_TEMPLATE_UPLOADS
} from '../actions/templates'
/* Utilities */
import { hasPendingUpload, isPendingUpload } from '../utilities/Template/hasPendingUpload'

/**
 * Our Redux Template Reducer that take the objects returned from our Redux Template Actions
 * and updates the template portion of our store
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * Setup the initial state of the "template" portion of our Redux store
 *
 * @type {{list: any, activeTemplate: (any), search: string}}
 *
 * @since 4.1
 */
export const initialState = {
  list: GFPDF.templateList,
  activeTemplate: GFPDF.activeTemplate || GFPDF.activeDefaultTemplate,
  search: '',
  updateSelectBoxText: '',
  templateProcessing: '',
  templateUploads: []
}

/**
 * The uploads a new file joins: the batch in flight, or a fresh one once every upload has reported back
 *
 * Each upload is `{ id, filename, status, message }`, where status is pending, success or failed
 *
 * @param {Array} uploads
 *
 * @returns {Array} The batch to add to
 *
 * @since 6.18.0
 */
const currentBatch = (uploads) => hasPendingUpload(uploads) ? uploads : []

/**
 * Keep the uploads that pass the check, returning the same state when none are dropped
 *
 * @param {Object} state
 * @param {Function} keep Called with each upload
 *
 * @returns {Object} The new state
 *
 * @since 6.18.0
 */
const keepUploads = (state, keep) => {
  const templateUploads = state.templateUploads.filter(keep)

  return templateUploads.length === state.templateUploads.length ? state : { ...state, templateUploads }
}

/**
 * Update one upload in the batch
 *
 * @param {Array} uploads
 * @param {number} id
 * @param {Object} changes
 *
 * @returns {Array} The updated batch
 *
 * @since 6.18.0
 */
const updateUpload = (uploads, id, changes) =>
  uploads.map((upload) => upload.id === id ? { ...upload, ...changes } : upload)

/**
 * Add newly-installed templates to the list, and flag the ones that were already there as updated
 *
 * @param {Array} list
 * @param {Array} templates
 *
 * @returns {Array} The updated list
 *
 * @since 6.18.0
 */
const mergeInstalledTemplates = (list, templates) => {
  const installed = templates.filter((template) => !list.some((item) => item.id === template.id))

  return [
    ...list.map((item) => templates.some((template) => template.id === item.id)
      ? { ...item, message: GFPDF.templateSuccessfullyUpdated }
      : item
    ),
    /* `new` sorts them to the end of the list */
    ...installed.map((template) => ({ ...template, new: true, message: GFPDF.templateSuccessfullyInstalled }))
  ]
}

/**
 * The action template reducer which updates our state
 *
 * @param {Object} state The current state of our template store
 * @param {Object} action The Redux action details being triggered
 *
 * @returns {Object} State (whether updated or not)
 *
 * @since 4.1
 */
export default function (state = initialState, action) {
  switch (action.type) {
    /**
     * Update the search key
     *
     * @since 4.1
     */
    case SEARCH_TEMPLATES:
      return {
        ...state,
        search: action.text
      }

    /**
     * Update the activeTemplate key
     *
     * @since 4.1
     */
    case SELECT_TEMPLATE:
      return {
        ...state,
        activeTemplate: action.id
      }

    /**
     * Push a new template into List
     *
     * @since 4.1
     */
    case ADD_TEMPLATE:
      return {
        ...state,
        list: [...state.list, action.template]
      }

    /**
     * Update single parameter in template new value
     *
     * @since 4.1
     */
    case UPDATE_TEMPLATE_PARAM: {
      const updatedList = state.list.map(item => {
        if (item.id === action.id) {
          return { ...item, [action.name]: action.value }
        }
        return item
      })
      return {
        ...state,
        list: updatedList
      }
    }

    /**
     * Remove template from List
     *
     * @since 4.1
     */
    case DELETE_TEMPLATE: {
      const list = state.list.filter(item => item.id !== action.id)
      return {
        ...state,
        list: [...list]
      }
    }

    /**
     * Update the new Select Box DOM data
     *
     * @since 5.2
     */
    case UPDATE_SELECT_BOX_SUCCESS:
      return {
        ...state,
        updateSelectBoxText: action.payload
      }

    /**
     * Remove the PDF template automatically
     *
     * @since 5.2
     */
    case TEMPLATE_PROCESSING_SUCCESS:
      return {
        ...state,
        templateProcessing: action.payload
      }

    /**
     * Fires Re-add template to our list and display an appropriate inline error message
     *
     * @since 5.2
     */
    case TEMPLATE_PROCESSING_FAILED:
      return {
        ...state,
        templateProcessing: action.payload
      }

    /**
     * Clear/reset the templateProcessing state
     *
     * @since 5.2
     */
    case CLEAR_TEMPLATE_PROCESSING:
      return {
        ...state,
        templateProcessing: ''
      }

    /**
     * Add a zip to the upload batch
     *
     * @since 6.18.0
     */
    case POST_TEMPLATE_UPLOAD_PROCESSING:
      return {
        ...state,
        templateUploads: [
          ...currentBatch(state.templateUploads),
          { id: action.payload.id, filename: action.payload.filename, status: 'pending' }
        ]
      }

    /**
     * Record an installed zip and add its templates to the list
     *
     * @since 5.2
     */
    case TEMPLATE_UPLOAD_PROCESSING_SUCCESS:
      return {
        ...state,
        list: mergeInstalledTemplates(state.list, action.payload.templates),
        templateUploads: updateUpload(state.templateUploads, action.payload.id, { status: 'success' })
      }

    /**
     * Record a zip that failed to install
     *
     * @since 5.2
     */
    case TEMPLATE_UPLOAD_PROCESSING_FAILED:
      return {
        ...state,
        templateUploads: updateUpload(
          state.templateUploads,
          action.payload.id,
          { status: 'failed', message: action.payload.message }
        )
      }

    /**
     * Record the files refused before upload
     *
     * @since 6.18.0
     */
    case TEMPLATE_UPLOAD_REJECTED:
      return {
        ...state,
        templateUploads: [
          ...currentBatch(state.templateUploads),
          ...action.payload.map((rejection) => ({ ...rejection, status: 'failed' }))
        ]
      }

    /**
     * Drop the installed zips from the batch, which hides the success message
     *
     * @since 6.18.0
     */
    case DISMISS_TEMPLATE_UPLOAD_SUCCESS:
      return keepUploads(state, (upload) => upload.status !== 'success')

    /**
     * Keep only the uploads still in flight
     *
     * @since 6.18.0
     */
    case CLEAR_FINISHED_TEMPLATE_UPLOADS:
      return keepUploads(state, isPendingUpload)
  }

  /* None of these actions fired so return state */
  return state
}
