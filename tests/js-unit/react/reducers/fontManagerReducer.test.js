import {
  GET_CUSTOM_FONT_LIST,
  GET_CUSTOM_FONT_LIST_SUCCESS,
  GET_CUSTOM_FONT_LIST_ERROR,
  ADD_FONT,
  ADD_FONT_SUCCESS,
  ADD_FONT_ERROR,
  EDIT_FONT,
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
} from '../../../../src/assets/js/react/actions/fontManager'
import reducer, { initialState } from '../../../../src/assets/js/react/reducers/fontManagerReducer'

describe('Reducers - fontManagerReducer.js', () => {

  let state
  let newState

  describe('GET_CUSTOM_FONT_LIST', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: GET_CUSTOM_FONT_LIST })

      expect(state.loading).toBe(true)
      expect(state.msg).toEqual({})
    })
  })

  describe('GET_CUSTOM_FONT_LIST_SUCCESS', () => {
    const data = [{
      font_name: 'Arial',
      id: 'arial',
      useOTL: 255,
      useKashida: 75,
      regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-regular.ttf',
      italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-italics.ttf',
      bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bold.ttf',
      bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bolditalics.ttf'
    }]

    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, {
        type: GET_CUSTOM_FONT_LIST_SUCCESS,
        payload: data
      })

      expect(state.loading).toBe(false)
      expect(state.fontList).toEqual(data)
    })
  })

  describe('GET_CUSTOM_FONT_LIST_ERROR', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, {
        type: GET_CUSTOM_FONT_LIST_ERROR,
        payload: 'error'
      })

      expect(state.loading).toBe(false)
      expect(state.msg.error.fontList).toBe('error')
    })
  })

  describe('ADD_FONT', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: ADD_FONT })

      expect(state.addFontLoading).toBe(true)
      expect(state.msg).toEqual({})
    })
  })

  describe('ADD_FONT_SUCCESS', () => {
    const data = {
      font: {
        font_name: 'Arial',
        id: 'arial',
        useOTL: 255,
        useKashida: 75,
        regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-regular.ttf',
        italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-italics.ttf',
        bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bold.ttf',
        bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bolditalics.ttf'
      },
      msg: 'success'
    }

    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, {
        type: ADD_FONT_SUCCESS,
        payload: data
      })

      expect(state.addFontLoading).toBe(false)
      expect(state.fontList).toEqual([data.font])
      expect(state.searchResult).toBe(null)
      expect(state.msg.success.addFont).toBe('success')

      newState = reducer(state, {
        type: ADD_FONT_SUCCESS,
        payload: data
      })

      expect(newState.addFontLoading).toBe(false)
      expect(newState.msg.success.addFont).toBe('success')
    })
  })

  describe('ADD_FONT_ERROR & EDIT_FONT_ERROR', () => {
    test('check the correct state gets returned when this action runs', () => {
      const data = {
        fontValidationError: 'error',
        msg: 'error'
      }

      state = reducer(initialState, {
        type: ADD_FONT_ERROR,
        payload: data
      })

      expect(state.addFontLoading).toBe(false)
      expect(state.msg.error.addFont).toBe('error')
      expect(state.msg.error.fontValidationError).toBe('error')
      expect(state.msg.error.deleteFont).toBe(undefined)
    })
  })

  describe('EDIT_FONT', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: EDIT_FONT })

      expect(state.addFontLoading).toBe(true)
      expect(state.msg).toEqual({})
    })
  })

  describe('EDIT_FONT_SUCCESS', () => {
    test('check the correct state gets returned when this action runs', () => {
      let initialstate
      const data = {
        font: {
          font_name: 'Arial',
          id: 'arial',
          useOTL: 255,
          useKashida: 75,
          regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-regular.ttf',
          italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-italics.ttf',
          bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bold.ttf',
          bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bolditalics.ttf'
        },
        msg: 'success'
      }
      initialstate = {
        loading: false,
        addFontLoading: false,
        deleteFontLoading: false,
        fontList: [data.font],
        searchResult: null,
        selectedFont: '',
        msg: {}
      }

      state = reducer(initialstate, {
        type: EDIT_FONT_SUCCESS,
        payload: data
      })

      expect(state.addFontLoading).toBe(false)
      expect(state.fontList).toEqual([data.font])
      expect(state.msg.success.addFont).toBe('success')

      initialstate = {
        loading: false,
        addFontLoading: false,
        deleteFontLoading: false,
        fontList: [data.font],
        searchResult: [data.font],
        selectedFont: '',
        msg: {}
      }

      newState = reducer(initialstate, {
        type: EDIT_FONT_SUCCESS,
        payload: data
      })

      expect(newState.addFontLoading).toBe(false)
      expect(newState.fontList).toEqual([data.font])
      expect(newState.searchResult).toEqual([data.font])
      expect(newState.msg.success.addFont).toBe('success')
    })
  })

  describe('VALIDATION_ERROR', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: VALIDATION_ERROR })

      expect(state.msg.error.addFont).toBe('')
    })
  })

  describe('DELETE_VARIANT_ERROR', () => {
    test('check the correct state gets returned when this action runs', () => {
      const initialstate = {
        msg: { error: { addFont: { italics: 'Cannot find Roboto-RegularItalic.ttf' } } }
      }

      state = reducer(initialstate, {
        type: DELETE_VARIANT_ERROR,
        payload: 'italics'
      })

      expect(state.msg.error.addFont).toEqual({})
    })
  })

  describe('DELETE_FONT', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: DELETE_FONT })

      expect(state.deleteFontLoading).toBe(true)
      expect(state.msg).toEqual({})
    })
  })

  describe('DELETE_FONT_SUCCESS', () => {
    test('check the correct state gets returned when this action runs', () => {
      let initialstate
      const data = [
        {
          font_name: 'Arial',
          id: 'arial',
          useOTL: 255,
          useKashida: 75,
          regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-regular.ttf',
          italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-italics.ttf',
          bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bold.ttf',
          bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bolditalics.ttf'
        },
        {
          font_name: 'Roboto',
          id: 'roboto',
          useOTL: 255,
          useKashida: 75,
          regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/roboto.ttf',
          italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/roboto.ttf',
          bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/roboto.ttf',
          bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/aroboto.ttf'
        }
      ]

      initialstate = { fontList: data }

      state = reducer(initialstate, {
        type: DELETE_FONT_SUCCESS,
        payload: 'arial'
      })

      expect(state.deleteFontLoading).toBe(false)
      expect(state.fontList).toEqual([data[1]])

      initialstate = {
        fontList: data,
        searchResult: data
      }

      newState = reducer(initialstate, {
        type: DELETE_FONT_SUCCESS,
        payload: 'roboto'
      })

      expect(newState.deleteFontLoading).toBe(false)
      expect(newState.fontList).toEqual([data[0]])
      expect(newState.searchResult).toEqual([data[0]])
    })
  })

  describe('DELETE_FONT_ERROR', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, {
        type: DELETE_FONT_ERROR,
        payload: 'A problem occurred. Reload the page and try again.'
      })

      expect(state.deleteFontLoading).toBe(false)
      expect(state.msg.error.deleteFont).toBe('A problem occurred. Reload the page and try again.')
    })
  })

  describe('CLEAR_ADD_FONT_MSG', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: CLEAR_ADD_FONT_MSG })

      expect(state.msg).toEqual({})
    })
  })

  describe('CLEAR_DROPZONE_ERROR', () => {
    test('check the correct state gets returned when this action runs', () => {
      const initialstate = { msg: { error: { addFont: { regular: 'error' } } } }

      state = reducer(initialstate, {
        type: CLEAR_DROPZONE_ERROR,
        payload: 'regular'
      })

      expect(state.msg.error.addFont).toEqual({})
    })
  })

  describe('RESET_SEARCH_RESULT', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, { type: RESET_SEARCH_RESULT })

      expect(state.searchResult).toBe(null)
    })
  })

  describe('SEARCH_FONT_LIST', () => {
    test('check the correct state gets returned when this action runs', () => {
      const initialstate = {
        fontList: [
          {
            font_name: 'Arial',
            id: 'arial',
            useOTL: 255,
            useKashida: 75,
            regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-regular.ttf',
            italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-italics.ttf',
            bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bold.ttf',
            bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/arial-bolditalics.ttf'
          },
          {
            font_name: 'Roboto',
            id: 'roboto',
            useOTL: 255,
            useKashida: 75,
            regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/roboto.ttf',
            italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/roboto.ttf',
            bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/roboto.ttf',
            bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/aroboto.ttf'
          }
        ]
      }

      state = reducer(initialstate, {
        type: SEARCH_FONT_LIST,
        payload: 'robot'
      })

      expect(state.searchResult).toEqual([initialstate.fontList[1]])

      newState = reducer(initialstate, {
        type: SEARCH_FONT_LIST,
        payload: ''
      })

      expect(newState.searchResult).toEqual(initialstate.fontList)
    })
  })

  describe('SELECT_FONT', () => {
    test('check the correct state gets returned when this action runs', () => {
      state = reducer(initialState, {
        type: SELECT_FONT,
        payload: 'roboto'
      })

      expect(state.selectedFont).toBe('roboto')
    })
  })
})
