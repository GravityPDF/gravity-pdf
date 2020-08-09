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

export const getCustomFontList = () => {
  return {
    type: GET_CUSTOM_FONT_LIST
  }
}

export const addFont = font => {
  return {
    type: ADD_FONT,
    payload: font
  }
}

export const editFont = fontDetails => {
  return {
    type: EDIT_FONT,
    payload: fontDetails
  }
}

export const validationError = () => {
  return {
    type: VALIDATION_ERROR
  }
}

export const deleteVariantError = fontVariant => {
  return {
    type: DELETE_VARIANT_ERROR,
    payload: fontVariant
  }
}

export const deleteFont = id => {
  return {
    type: DELETE_FONT,
    payload: id
  }
}

export const clearAddFontMsg = () => {
  return {
    type: CLEAR_ADD_FONT_MSG
  }
}

export const clearDropzoneError = key => {
  return {
    type: CLEAR_DROPZONE_ERROR,
    payload: key
  }
}

export const searchFontList = data => {
  return {
    type: SEARCH_FONT_LIST,
    payload: data
  }
}

export const resetSearchResult = () => {
  return {
    type: RESET_SEARCH_RESULT
  }
}

export const selectFont = fontId => {
  return {
    type: SELECT_FONT,
    payload: fontId
  }
}
