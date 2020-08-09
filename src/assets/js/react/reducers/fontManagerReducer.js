import { sprintf } from 'sprintf-js'
import {
  GET_CUSTOM_FONT_LIST,
  GET_CUSTOM_FONT_LIST_SUCCESS,
  GET_CUSTOM_FONT_LIST_ERROR,
  ADD_FONT,
  ADD_FONT_SUCCESS,
  ADD_FONT_ERROR,
  EDIT_FONT,
  EDIT_FONT_ERROR,
  EDIT_FONT_SUCCESS,
  VALIDATION_ERROR,
  DELETE_VARIANT_ERROR,
  DELETE_FONT,
  DELETE_FONT_SUCCESS,
  DELETE_FONT_ERROR,
  CLEAR_ADD_FONT_MSG,
  CLEAR_DROPZONE_ERROR,
  RESET_SEARCH_RESULT,
  SEARCH_FONT_LIST,
  SELECT_FONT
} from '../actions/fontManager'
import {
  findAndUpdate,
  findAndRemove,
  reduceFontFileName,
  checkFontListIncludes,
  clearMsg
} from '../utilities/fontManagerReducer'

export const initialState = {
  loading: false,
  addFontLoading: false,
  deleteFontLoading: false,
  fontList: [],
  searchResult: null,
  selectedFont: '',
  msg: {}
}

export default function (state = initialState, action) {
  const { payload } = action

  switch (action.type) {
    case GET_CUSTOM_FONT_LIST: {
      return {
        ...state,
        loading: true,
        msg: {}
      }
    }

    case GET_CUSTOM_FONT_LIST_SUCCESS:
      return {
        ...state,
        loading: false,
        fontList: payload
      }

    case GET_CUSTOM_FONT_LIST_ERROR:
      return {
        ...state,
        loading: false,
        msg: { error: { fontList: payload } }
      }

    case ADD_FONT: {
      const msg = { ...state.msg }

      /* Clear previous fontValidation error msg */
      if (msg.error && msg.error.fontValidationError) {
        delete msg.error.fontValidationError
      }

      return {
        ...state,
        addFontLoading: true,
        msg: clearMsg({ ...msg })
      }
    }

    case ADD_FONT_SUCCESS: {
      if (state.msg.error && state.msg.error.fontList) {
        return {
          ...state,
          addFontLoading: false,
          msg: { ...state.msg, success: { addFont: payload.msg } }
        }
      }

      const updatedFontList = [...state.fontList, payload.font]

      return {
        ...state,
        addFontLoading: false,
        fontList: updatedFontList,
        searchResult: state.searchResult ? updatedFontList : null,
        msg: { success: { addFont: payload.msg } }
      }
    }

    case ADD_FONT_ERROR:
    case EDIT_FONT_ERROR: {
      let msg

      msg = { ...state.msg, error: { ...state.msg.error, addFont: payload } }

      if (payload.fontValidationError) {
        msg = {
          ...state.msg,
          error: {
            ...state.msg.error,
            addFont: payload.msg,
            fontValidationError: sprintf(GFPDF.fontFileInvalid, '<strong>', '</strong>')
          }
        }
      }

      /* Clear deleteFont error msg */
      if (msg.error && msg.error.deleteFont) {
        delete msg.error.deleteFont
      }

      return {
        ...state,
        addFontLoading: false,
        msg
      }
    }

    case EDIT_FONT: {
      const msg = { ...state.msg }

      /* Clear previous success msg */
      if (msg.success) {
        delete msg.success
      }

      /* Clear previous addFont error msg */
      if (msg.error && msg.error.addFont) {
        delete msg.error.addFont
      }

      /* Clear previous fontValidation error msg */
      if (msg.error && msg.error.fontValidationError) {
        delete msg.error.fontValidationError
      }

      return {
        ...state,
        addFontLoading: true,
        msg
      }
    }

    /**
     * Update fontList state with the new font details
     */
    case EDIT_FONT_SUCCESS: {
      const msg = { success: { addFont: payload.msg } }

      /* Update search result in case there's an ongoing search */
      if (state.searchResult) {
        return {
          ...state,
          addFontLoading: false,
          fontList: findAndUpdate([...state.fontList], payload),
          searchResult: findAndUpdate([...state.searchResult], payload),
          msg
        }
      }

      return {
        ...state,
        addFontLoading: false,
        fontList: findAndUpdate([...state.fontList], payload),
        msg
      }
    }

    case VALIDATION_ERROR:
      return {
        ...state,
        msg: {
          error: {
            ...state.msg.error,
            addFont: sprintf(GFPDF.addUpdateFontError, '<strong>', '</strong>')
          }
        }
      }

    case DELETE_VARIANT_ERROR: {
      const addFont = { ...state.msg.error.addFont }
      delete addFont[payload]

      return {
        ...state,
        msg: { error: { ...state.msg.error, addFont } }
      }
    }

    case DELETE_FONT: {
      const msg = { ...state.msg }

      /* Clear previous success msg */
      if (msg.success) {
        delete msg.success
      }

      /* Clear previous deleteFont error msg */
      if (msg.error && msg.error.deleteFont) {
        delete msg.error.deleteFont
      }

      return {
        ...state,
        deleteFontLoading: true,
        msg
      }
    }

    case DELETE_FONT_SUCCESS: {
      /* Delete from the list during active search */
      if (state.searchResult) {
        return {
          ...state,
          deleteFontLoading: false,
          fontList: findAndRemove([...state.fontList], payload),
          searchResult: findAndRemove([...state.searchResult], payload).length === 0 ? null : findAndRemove([...state.searchResult], payload)
        }
      }

      return {
        ...state,
        deleteFontLoading: false,
        fontList: findAndRemove([...state.fontList], payload)
      }
    }

    case DELETE_FONT_ERROR:
      return {
        ...state,
        deleteFontLoading: false,
        msg: { ...state.msg, error: { ...state.msg.error, deleteFont: payload } }
      }

    case CLEAR_ADD_FONT_MSG:
      return {
        ...state,
        msg: clearMsg({ ...state.msg })
      }

    case CLEAR_DROPZONE_ERROR: {
      const addFont = state.msg.error.addFont
      typeof addFont === 'object' && delete addFont[payload]

      return {
        ...state,
        msg: { ...state.msg, error: { ...state.msg.error, addFont } }
      }
    }

    case RESET_SEARCH_RESULT:
      return {
        ...state,
        searchResult: null
      }

    case SEARCH_FONT_LIST: {
      const fontList = [...state.fontList]

      if (payload === '') {
        return {
          ...state,
          searchResult: fontList
        }
      }

      const keyword = payload.toLowerCase()
      const searchResult = []
      const modifiedFontList = fontList.map(font => {
        font.regular = reduceFontFileName(font.regular)
        font.italics = reduceFontFileName(font.italics)
        font.bold = reduceFontFileName(font.bold)
        font.bolditalics = reduceFontFileName(font.bolditalics)

        return { ...font }
      })

      modifiedFontList.map(font => {
        if (
          checkFontListIncludes(font.font_name, keyword) ||
          checkFontListIncludes(font.regular, keyword) ||
          checkFontListIncludes(font.italics, keyword) ||
          checkFontListIncludes(font.bold, keyword) ||
          checkFontListIncludes(font.bolditalics, keyword)
        ) {
          searchResult.push(font)
        }
      })

      const relevant = []
      const related = []

      /* Construct 2 arrays containing the most relevant and the related results */
      searchResult.map(item => {
        if (item.font_name.toLowerCase().includes(keyword)) {
          return relevant.push(item)
        }

        related.push(item)
      })

      /* Sort and combine mostRelevant and related array into 1 array */
      const result = [
        ...relevant.sort((a, b) => a.font_name.localeCompare(b.font_name)),
        ...related.sort((a, b) => a.font_name.localeCompare(b.font_name))
      ]

      return {
        ...state,
        searchResult: result
      }
    }

    case SELECT_FONT:
      return {
        ...state,
        selectedFont: payload
      }

    default:
      return state
  }
}
