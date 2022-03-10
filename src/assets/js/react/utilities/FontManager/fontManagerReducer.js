/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * This function is used to generate a new array by mapping
 * redux store "fontList" or "searchResult" state with the
 * current payload data
 *
 * @param data: array of object
 * @param payload: object
 *
 * @returns { array of object }
 *
 * @since 6.0
 */
export function findAndUpdate (data, payload) {
  const list = [...data]

  list.map(font => {
    if (font.id === payload.font.id) {
      font.font_name = payload.font.font_name
      font.regular = payload.font.regular
      font.italics = payload.font.italics
      font.bold = payload.font.bold
      font.bolditalics = payload.font.bolditalics
    }
  })

  return list
}

/**
 * This function is used to filter or remove payload data from the current
 * redux store "fontList" or "searchResult" state
 *
 * @param data: array of object
 * @param payload: string
 *
 * @returns { array }
 *
 * @since 6.0
 */
export function findAndRemove (data, payload) {
  const list = [...data]
  const newList = list.filter(font => font.id !== payload)

  return newList
}

export function reduceFontFileName (key) {
  return key
    .substr(key.lastIndexOf('/') + 1)
}

/**
 * This function is used to check string if it's a match with the current keyword
 *
 * @param font: string
 * @param keyword: string
 *
 * @returns { boolean }
 *
 * @since 6.0
 */
export function checkFontListIncludes (font, keyword) {
  return font
    .replace('.ttf', '')
    .toLowerCase()
    .includes(keyword)
}

/**
 * This function is used to clear/reset redux store "msg" state
 *
 * @param payload: object
 *
 * @returns { object }
 *
 * @since 6.0
 */
export function clearMsg (payload) {
  const msg = { ...payload }

  /* Clear previous success msg */
  if (msg.success) {
    delete msg.success
  }

  /* Clear previous addFont error msg */
  if (msg.error && msg.error.addFont) {
    delete msg.error.addFont
  }

  return msg
}
