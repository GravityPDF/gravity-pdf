export const GET_CUSTOM_FONT_LIST = 'GET_CUSTOM_FONT_LIST'
export const GET_CUSTOM_FONT_LIST_SUCCESS = 'GET_CUSTOM_FONT_LIST_SUCCESS'
export const ADD_FONT = 'ADD_FONT'
export const ADD_FONT_SUCCESS = 'ADD_FONT_SUCCESS'
export const EDIT_FONT = 'EDIT_FONT'
export const EDIT_FONT_SUCCESS = 'EDIT_FONT_SUCCESS'
export const DELETE_FONT = 'DELETE_FONT'
export const DELETE_FONT_SUCCESS = 'DELETE_FONT_SUCCESS'

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

export const editFont = font => {
  return {
    type: EDIT_FONT,
    payload: font
  }
}

export const deleteFont = id => {
  return {
    type: DELETE_FONT,
    payload: id
  }
}
