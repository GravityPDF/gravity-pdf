/* Dependencies */
import { sprintf } from 'sprintf-js'
/* Redux action types */
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
  SELECT_FONT,
  MOVE_SELECTED_FONT_TO_TOP
} from '../actions/fontManager'
/* Utilities */
import {
  findAndUpdate,
  findAndRemove,
  reduceFontFileName,
  checkFontListIncludes,
  clearMsg
} from '../utilities/FontManager/fontManagerReducer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Initial state setup for the "fontManager" portion of our redux store
 *
 * @type {{
 *  loading: boolean,
 *  addFontLoading: boolean,
 *  deleteFontLoading: boolean,
 *  fontList: array,
 *  searchResult: null,
 *  selectedFont: string,
 *  msg: object
 * }}
 *
 * @since 6.0
 */
export const initialState = {
  loading: false,
  addFontLoading: false,
  deleteFontLoading: false,
  fontList: [],
  searchResult: null,
  selectedFont: '',
  msg: {}
}

/**
 * The action for "fontManager" reducer which updates its state
 *
 * @param state: object
 * @param action: object
 *
 * @returns {{ updated state }}
 *
 * @since 6.0
 */
export default function (state = initialState, action) {
  const { payload } = action

  switch (action.type) {
    /**
     * Process GET_CUSTOM_FONT_LIST
     *
     * @since 6.0
     */
    case GET_CUSTOM_FONT_LIST: {
      return {
        ...state,
        loading: true,
        msg: {}
      }
    }

    /**
     * Process GET_CUSTOM_FONT_LIST_SUCCESS
     *
     * @since 6.0
     */
    case GET_CUSTOM_FONT_LIST_SUCCESS:
      return {
        ...state,
        loading: false,
        fontList: payload
      }

    /**
     * Process GET_CUSTOM_FONT_LIST_ERROR
     *
     * @since 6.0
     */
    case GET_CUSTOM_FONT_LIST_ERROR:
      return {
        ...state,
        loading: false,
        msg: { error: { fontList: payload } }
      }

    /**
     * Process ADD_FONT
     *
     * @since 6.0
     */
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

    /**
     * Process ADD_FONT_SUCCESS
     *
     * @since 6.0
     */
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

    /**
     * Process ADD_FONT_ERROR & EDIT_FONT_ERROR
     *
     * @since 6.0
     */
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
            fontValidationError: sprintf(payload.fontValidationError, '<strong>', '</strong>')
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

    /**
     * Process EDIT_FONT
     *
     * @since 6.0
     */
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
     * Process EDIT_FONT_SUCCESS
     *
     * Update fontList state with the new font details
     *
     * @since 6.0
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

    /**
     * Process VALIDATION_ERROR
     *
     * @since 6.0
     */
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

    /**
     * Process DELETE_VARIANT_ERROR
     *
     * @since 6.0
     */
    case DELETE_VARIANT_ERROR: {
      const addFont = { ...state.msg.error.addFont }
      delete addFont[payload]

      return {
        ...state,
        msg: { error: { ...state.msg.error, addFont } }
      }
    }

    /**
     * Process DELETE_FONT
     *
     * @since 6.0
     */
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

    /**
     * Process DELETE_FONT_SUCCESS
     *
     * @since 6.0
     */
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

    /**
     * Process DELETE_FONT_ERROR
     *
     * @since 6.0
     */
    case DELETE_FONT_ERROR:
      return {
        ...state,
        deleteFontLoading: false,
        msg: { ...state.msg, error: { ...state.msg.error, deleteFont: payload } }
      }

    /**
     * Process CLEAR_ADD_FONT_MSG
     *
     * @since 6.0
     */
    case CLEAR_ADD_FONT_MSG:
      return {
        ...state,
        msg: clearMsg({ ...state.msg })
      }

    /**
     * Process CLEAR_DROPZONE_ERROR
     *
     * @since 6.0
     */
    case CLEAR_DROPZONE_ERROR: {
      const addFont = state.msg.error.addFont
      typeof addFont === 'object' && delete addFont[payload]

      return {
        ...state,
        msg: { ...state.msg, error: { ...state.msg.error, addFont } }
      }
    }

    /**
     * Process RESET_SEARCH_RESULT
     *
     * @since 6.0
     */
    case RESET_SEARCH_RESULT:
      return {
        ...state,
        searchResult: null
      }

    /**
     * Process SEARCH_FONT_LIST
     *
     * @since 6.0
     */
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
          return searchResult.push(font)
        }

        return false
      })

      const relevant = []
      const related = []

      /* Construct 2 arrays containing the most relevant and the related results */
      searchResult.map(item => {
        if (item.font_name.toLowerCase().includes(keyword)) {
          return relevant.push(item)
        }

        return related.push(item)
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

    /**
     * Process SELECT_FONT
     *
     * @since 6.0
     */
    case SELECT_FONT:
      return {
        ...state,
        selectedFont: payload
      }

    /**
     * Process MOVE_SELECTED_FONT_TO_TOP
     *
     * @since 6.0
     */
    case MOVE_SELECTED_FONT_TO_TOP: {
      const fontList = [...state.fontList]
      const filterFontList = fontList.filter(item => item.id !== payload)
      const getPayloadItem = fontList.filter(item => item.id === payload)
      const list = [...getPayloadItem, ...filterFontList]

      return {
        ...state,
        fontList: list
      }
    }
  }

  return state
}
