import {
  getCustomFontList,
  GET_CUSTOM_FONT_LIST,
  addFont,
  ADD_FONT,
  editFont,
  EDIT_FONT,
  validationError,
  VALIDATION_ERROR,
  deleteVariantError,
  DELETE_VARIANT_ERROR,
  deleteFont,
  DELETE_FONT,
  clearAddFontMsg,
  CLEAR_ADD_FONT_MSG,
  clearDropzoneError,
  CLEAR_DROPZONE_ERROR,
  searchFontList,
  SEARCH_FONT_LIST,
  resetSearchResult,
  RESET_SEARCH_RESULT,
  selectFont,
  SELECT_FONT
} from '../../../../src/assets/js/react/actions/fontManager'

describe('Redux actions - fontManager.js', () => {

  let results

  test('getCustomFontList - check if it returns the correct action', () => {
    results = getCustomFontList()

    expect(results.type).toEqual(GET_CUSTOM_FONT_LIST)
  })

  test('addFont - check if it returns the correct action', () => {
    results = addFont('gotham')

    expect(results.type).toEqual(ADD_FONT)
    expect(results.payload).toBe('gotham')
  })

  test('editFont - check if it returns the correct action', () => {
    results = editFont({})

    expect(results.type).toEqual(EDIT_FONT)
    expect(results.payload).toEqual({})
  })

  test('validationError - check if it returns the correct action', () => {
    results = validationError()

    expect(results.type).toEqual(VALIDATION_ERROR)
  })

  test('deleteVariantError - check if it returns the correct action', () => {
    results = deleteVariantError('italic')

    expect(results.type).toEqual(DELETE_VARIANT_ERROR)
    expect(results.payload).toBe('italic')
  })

  test('deleteFont - check if it returns the correct action', () => {
    results = deleteFont('gotham')

    expect(results.type).toEqual(DELETE_FONT)
    expect(results.payload).toBe('gotham')
  })

  test('clearAddFontMsg - check if it returns the correct action', () => {
    results = clearAddFontMsg()

    expect(results.type).toEqual(CLEAR_ADD_FONT_MSG)
  })

  test('clearDropzoneError - check if it returns the correct action', () => {
    results = clearDropzoneError('error')

    expect(results.type).toEqual(CLEAR_DROPZONE_ERROR)
    expect(results.payload).toEqual('error')
  })

  test('searchFontList - check if it returns the correct action', () => {
    results = searchFontList('Gotham')

    expect(results.type).toEqual(SEARCH_FONT_LIST)
    expect(results.payload).toEqual('Gotham')
  })

  test('resetSearchResult - check if it returns the correct action', () => {
    results = resetSearchResult()

    expect(results.type).toEqual(RESET_SEARCH_RESULT)
  })

  test('selectFont - check if it returns the correct action', () => {
    results = selectFont('gotham')

    expect(results.type).toEqual(SELECT_FONT)
    expect(results.payload).toBe('gotham')
  })
})
