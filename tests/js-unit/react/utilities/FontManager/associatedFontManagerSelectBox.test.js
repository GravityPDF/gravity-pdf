import { associatedFontManagerSelectBox } from '../../../../../src/assets/js/react/utilities/FontManager/associatedFontManagerSelectBox'

describe('Utilities/FontManager - associatedFontManagerSelectBox.js', () => {

  const fontList = [
    {
      bold: '',
      bolditalics: '',
      font_name: 'Gugi',
      id: 'gugi',
      italics: '',
      regular: 'Gugi-Regular.ttf'
    },
    {
      bold: '',
      bolditalics: '',
      font_name: 'Gotham',
      id: 'gotham',
      italics: '',
      regular: 'Gotham-Black-Regular.ttf'
    }
  ]

  beforeEach(() => {
    // Mock font manager select box DOM
    document.body.innerHTML =
      '<div class="gfpdf-font-manager">' +
      ' <select id="gfpdf_settings[default_font]" class="gfpdf_settings_default_font " name="gfpdf_settings[default_font]" data-placeholder="">' +
      '   <optgroup label="User-Defined Fonts">' +
      '     <option value="gugi">Gugi</option>' +
      '     <option value="gotham">Gotham</option>' +
      '   </optgroup>' +
      ' </select>' +
      '</div>'
  })

  test('associatedFontManagerSelectBox() - Set current selected font value', () => {
    expect(associatedFontManagerSelectBox(fontList, 'gugi')).toBe('gugi')
  })

  test('associatedFontManagerSelectBox() - Assign default value if selected item is deleted', () => {
    expect(associatedFontManagerSelectBox(fontList, 'arial')).toBe('0')
  })
})
