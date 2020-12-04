/* Redux action types */
export const GET_CUSTOM_FONT_LIST = 'GET_CUSTOM_FONT_LIST'
export const GET_CUSTOM_FONT_LIST_SUCCESS = 'GET_CUSTOM_FONT_LIST_SUCCESS'
export const GET_CUSTOM_FONT_LIST_ERROR = 'GET_CUSTOM_FONT_LIST_ERROR'
export const ADD_FONT = 'ADD_FONT'
export const ADD_FONT_SUCCESS = 'ADD_FONT_SUCCESS'
export const ADD_FONT_ERROR = 'ADD_FONT_ERROR'
export const EDIT_FONT = 'EDIT_FONT'
export const EDIT_FONT_SUCCESS = 'EDIT_FONT_SUCCESS'
export const EDIT_FONT_ERROR = 'EDIT_FONT_ERROR'
export const VALIDATION_ERROR = 'VALIDATION_ERROR'
export const DELETE_VARIANT_ERROR = 'DELETE_VARIANT_ERROR'
export const DELETE_FONT = 'DELETE_FONT'
export const DELETE_FONT_SUCCESS = 'DELETE_FONT_SUCCESS'
export const DELETE_FONT_ERROR = 'DELETE_FONT_ERROR'
export const CLEAR_ADD_FONT_MSG = 'CLEAR_ADD_FONT_MSG'
export const CLEAR_DROPZONE_ERROR = 'CLEAR_DROPZONE_ERROR'
export const RESET_SEARCH_RESULT = 'RESET_SEARCH_RESULT'
export const SEARCH_FONT_LIST = 'SEARCH_FONT_LIST'
export const SELECT_FONT = 'SELECT_FONT'
export const MOVE_SELECTED_FONT_TO_TOP = 'MOVE_SELECTED_FONT_TO_TOP'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Get the custom font list data
 *
 * @returns {{ type: string }}
 *
 * @since 6.0
 */
export const getCustomFontList = () => {
  return {
    type: GET_CUSTOM_FONT_LIST
  }
}

/**
 * Add font request
 *
 * @param font
 *
 * @returns {{ payload: object, type: string }}
 *
 * @since 6.0
 */
export const addFont = font => {
  return {
    type: ADD_FONT,
    payload: font
  }
}

/**
 * Edit font request
 *
 * @param fontDetails
 *
 * @returns {{ payload: object, type: string }}
 *
 * @since 6.0
 */
export const editFont = fontDetails => {
  return {
    type: EDIT_FONT,
    payload: fontDetails
  }
}

/**
 * Process validation error
 *
 * @returns {{ type: string }}
 *
 * @since 6.0
 */
export const validationError = () => {
  return {
    type: VALIDATION_ERROR
  }
}

/**
 * Process deletion of font variant if error exist (regular, italics, bold, bold italics)
 *
 * @param fontVariant
 *
 * @returns {{ payload: string, type: string }}
 *
 * @since 6.0
 */
export const deleteVariantError = fontVariant => {
  return {
    type: DELETE_VARIANT_ERROR,
    payload: fontVariant
  }
}

/**
 * Delete font request
 *
 * @param id
 *
 * @returns {{ payload: string, type: string }}
 *
 * @since 6.0
 */
export const deleteFont = id => {
  return {
    type: DELETE_FONT,
    payload: id
  }
}

/**
 * Process cleaning of success message and add font error message
 *
 * @returns {{ type: string }}
 *
 * @since 6.0
 */
export const clearAddFontMsg = () => {
  return {
    type: CLEAR_ADD_FONT_MSG
  }
}

/**
 * Process cleaning of dropzone error message
 *
 * @param key
 *
 * @returns {{ payload: string, type: string }}
 *
 * @since 6.0
 */
export const clearDropzoneError = key => {
  return {
    type: CLEAR_DROPZONE_ERROR,
    payload: key
  }
}

/**
 * Search font list request
 *
 * @param data
 *
 * @returns {{ payload: string, type: string }}
 *
 * @since 6.0
 */
export const searchFontList = data => {
  return {
    type: SEARCH_FONT_LIST,
    payload: data
  }
}

/**
 * Process cleaning of search result
 *
 * @returns {{ type: string }}
 *
 * @since 6.0
 */
export const resetSearchResult = () => {
  return {
    type: RESET_SEARCH_RESULT
  }
}

/**
 * Select font
 *
 * @param fontId
 *
 * @returns {{ payload: string, type: string }}
 *
 * @since 6.0
 */
export const selectFont = fontId => {
  return {
    type: SELECT_FONT,
    payload: fontId
  }
}

/**
 * Move selected font to top
 *
 * @param fontId
 *
 * @returns {{ payload: string, type: string }}
 */
export const moveSelectedFontToTop = fontId => {
  return {
    type: MOVE_SELECTED_FONT_TO_TOP,
    payload: fontId
  }
}
