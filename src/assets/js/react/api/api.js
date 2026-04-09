/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Wrapper for the fetch() API which return a promise response
 *
 * @param {string} url
 * @param {object} init
 *
 * @returns {Promise} response
 *
 * @since 6.0
 */
export const api = async (url, init) => {
  return await window.fetch(url, init)
}

/**
 * Try parse the API response as JSON, accounting for a PHP error output before the payload
 *
 * @param {string} str
 * @returns {object}
 */
export const getJsonString = (str) => {
  for (const character of ['{', '[']) {
    let testStr = str
    const index = testStr.indexOf(character)
    if (index > 0) {
      testStr = testStr.slice(index)
    }

    try {
      return JSON.parse(testStr)
    } catch (e) {}
  }

  console.error('Invalid API response', str)

  return {
    error: GFPDF.addFatalError
  }
}
