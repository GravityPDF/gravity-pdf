/* Dependencies */
import { serialize } from 'object-to-formdata'
/* APIs */
import { api } from './api'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Fetch API request to obtain custom font list (GET)
 *
 * @returns Promise response
 *
 * @since 6.0
 */
export const apiGetCustomFontList = () => {
  const url = GFPDF.restUrl + 'fonts/'

  return api(url, {
    method: 'GET',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    }
  })
}

/**
 * Fetch API request to add new font (POST)
 *
 * @param font: object
 *
 * @returns Promise response
 *
 * @since 6.0
 */
export const apiAddFont = font => {
  const url = GFPDF.restUrl + 'fonts/'
  const formData = serialize(font)

  return api(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    },
    body: formData
  })
}

/**
 * Fetch API request to edit font details (POST)
 *
 * @param id: string
 * @param font: object
 *
 * @returns Promise response
 *
 * @since 6.0
 */
export const apiEditFont = ({ id, font }) => {
  const url = GFPDF.restUrl + 'fonts/' + id
  const data = { ...font }
  const formData = serialize(data)

  return api(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    },
    body: formData
  })
}

/**
 * Fetch API request to delete existing font (DELETE)
 *
 * @param id: string
 *
 * @returns Promise response
 *
 * @since 6.0
 */
export const apiDeleteFont = id => {
  const url = GFPDF.restUrl + 'fonts/' + id

  return api(url, {
    method: 'DELETE',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    }
  })
}
