/* Dependencies */
import { serialize } from 'object-to-formdata'
/* APIs */
import { api, getJsonString } from './api'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Fetch API request to obtain custom font list (GET)
 *
 * @returns {Object}
 *
 * @since 6.0
 */
export const apiGetCustomFontList = async () => {
  const url = GFPDF.restUrl + 'fonts/'

  const response = await api(url, {
    method: 'GET',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce,
      Accept: 'application/json'
    }
  })

  const text = await response.text()
  const body = getJsonString(text)

  return {
    body,
    text,
    status: response.status,
    ok: response.ok
  }
}

/**
 * Fetch API request to add new font (POST)
 *
 * @param {object} font
 *
 * @returns {Object}
 *
 * @since 6.0
 */
export const apiAddFont = async font => {
  const url = GFPDF.restUrl + 'fonts/'
  const formData = serialize(font)

  const response = await api(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce,
      Accept: 'application/json'
    },
    body: formData
  })

  const text = await response.text()
  const body = getJsonString(text)

  return {
    body,
    text,
    status: response.status,
    ok: response.ok
  }
}

/**
 * Fetch API request to edit font details (POST)
 *
 * @param {string} id
 * @param {object} font
 *
 * @returns {Object}
 *
 * @since 6.0
 */
export const apiEditFont = async ({
  id,
  font
}) => {
  const url = GFPDF.restUrl + 'fonts/' + id
  const data = { ...font }
  const formData = serialize(data)

  const response = await api(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce,
      Accept: 'application/json'
    },
    body: formData
  })

  const text = await response.text()
  const body = getJsonString(text)

  return {
    body,
    text,
    status: response.status,
    ok: response.ok
  }
}

/**
 * Fetch API request to delete existing font (DELETE)
 *
 * @param {string} id
 *
 * @returns {Promise}
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
