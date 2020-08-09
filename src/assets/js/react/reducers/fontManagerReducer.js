import {
  GET_CUSTOM_FONT_LIST,
  GET_CUSTOM_FONT_LIST_SUCCESS,
  ADD_FONT_SUCCESS,
  EDIT_FONT_SUCCESS,
  DELETE_FONT_SUCCESS
} from '../actions/fontManager'

export const initialState = {
  fontList: [],
  editFontSuccess: false
}

export default function (state = initialState, action) {
  switch (action.type) {
    case GET_CUSTOM_FONT_LIST:
      return {

      }

    case GET_CUSTOM_FONT_LIST_SUCCESS:
      return {
        ...state,
        fontList: action.payload
      }

    case ADD_FONT_SUCCESS:
      return {
        ...state,
        fontList: [...state.fontList, action.payload]
      }

    case EDIT_FONT_SUCCESS:
      return {
        ...state,
        editFontSuccess: true
      }

    case DELETE_FONT_SUCCESS: {
      const fontList = [...state.fontList]
      const newFontList = fontList.filter(font => font.id !== action.payload)

      return {
        ...state,
        fontList: newFontList
      }
    }

    default:
      return state
  }
}
