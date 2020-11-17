/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * This function is used to update font manager "dropdown select box" selected value
 *
 * @param fontList: array of object
 * @param id string
 *
 * @since 6.0
 */
export function associatedFontManagerSelectBox (fontList, id) {
  const fontManagerSelectBox = document.querySelector('.gfpdf-font-manager select')
  const definedFontsOptgroup = document.querySelector('optgroup[label="User-Defined Fonts"]')
  const selectedValue = fontManagerSelectBox.options[fontManagerSelectBox.selectedIndex].value
  const userDefinedFonts = []

  /* Return default font value if selected font is not under user custom/defined font list */
  if (!id) {
    return
  }

  /* Get current User-Defined Fonts items */
  Array.from(document.querySelectorAll('optgroup[label="User-Defined Fonts"] > option')).map(item => userDefinedFonts.push(item.value))

  if (definedFontsOptgroup !== null) {
    /* Remove optgroup */
    definedFontsOptgroup.remove()
  }

  const optgroup = document.createElement('optgroup')
  optgroup.setAttribute('label', 'User-Defined Fonts')

  fontList.map(font => {
    const option = document.createElement('option')
    option.text = font.font_name
    option.value = font.id

    optgroup.appendChild(option)
  })

  const list = []

  if (fontList.length > 0) {
    fontList.map(font => list.push(font.id))
  }

  let updateSelectBoxValue

  /* Set current selected font value */
  if (list.length > 0 && list.includes(id)) {
    fontManagerSelectBox.insertBefore(optgroup, fontManagerSelectBox.childNodes[0])
    updateSelectBoxValue = fontManagerSelectBox.value = id

    return updateSelectBoxValue
  }

  /* Assign default value if selected item is deleted */
  if (list.length > 0 && !list.includes(id)) {
    fontManagerSelectBox.insertBefore(optgroup, fontManagerSelectBox.childNodes[0])
    updateSelectBoxValue = fontManagerSelectBox.selectedIndex = '0'

    return updateSelectBoxValue
  }

  /* Perform deletion for the very last item left */
  if (list.length === 0 && userDefinedFonts.length > 0) {
    updateSelectBoxValue = fontManagerSelectBox.selectedIndex = '0'

    return updateSelectBoxValue
  }

  fontManagerSelectBox.insertBefore(optgroup, fontManagerSelectBox.childNodes[0])
  fontManagerSelectBox.value = !id ? selectedValue : id

  /* Remove User-Defined Fonts field if empty */
  if (userDefinedFonts.length === 0 && list.length === 0) {
    fontManagerSelectBox.querySelector('optgroup[label="User-Defined Fonts"]').remove()
  }
}
