import {
  findAndUpdate,
  findAndRemove,
  reduceFontFileName,
  checkFontListIncludes,
  clearMsg
} from '../../../../../src/assets/js/react/utilities/FontManager/fontManagerReducer'

describe('Utilities/FontManager - fontManagerReducer.js', () => {

  const data = [
    {
      font_name: 'roboto',
      id: 'roboto',
      useKashida: 75,
      regular: 'roboto-regular.ttf',
      italics: 'roboto-italics.ttf',
      bold: 'roboto-bold.ttf',
      bolditalics: 'roboto-bolditalics.ttf'
    },
    {
      font_name: 'gotham',
      id: 'gotham',
      useKashida: 75,
      regular: 'gotham-regular.ttf',
      italics: 'gotham-italics.ttf',
      bold: 'gotham-bold.ttf',
      bolditalics: 'gotham-bolditalics.ttf'
    }
  ]

  test('findAndUpdate() - ', () => {
    const payload = {
      font: {
        font_name: 'gotham',
        id: 'gotham',
        useKashida: 75,
        regular: 'gotham-regular.ttf',
        italics: 'gotham-italics.ttf',
        bold: 'gotham-bold.ttf',
        bolditalics: 'gotham-bolditalics.ttf'
      },
      msg: '<strong>Your font has been saved.</strong>'
    }

    expect(findAndUpdate(data, payload)).toEqual(data)
  })

  test('findAndRemove() - ', () => {
    expect(findAndRemove(data, 'gotham')).toEqual([data[0]])
  })

  test('reduceFontFileName() - ', () => {
    expect(reduceFontFileName('wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/Roboto-Regular.ttf')).toBe('Roboto-Regular.ttf')
  })

  test('checkFontListIncludes() (true) - ', () => {
    expect(checkFontListIncludes('robotoo', 'roboto')).toBe(true)
  })

  test('checkFontListIncludes() (false) - ', () => {
    expect(checkFontListIncludes('gotham', 'roboto')).toBe(false)
  })

  test('clearMsg() - ', () => {
    const msg = {
      success: { addFont: 'success' },
      error: { addFont: 'error' }
    }
    expect(clearMsg(msg)).toEqual({ error: {} })
  })
})
