import { api } from './api'
import { serialize } from 'object-to-formdata'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
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

export const apiEditFont = ({ id, font }) => {
  const url = GFPDF.restUrl + 'fonts/' + id
  const formData = serialize(font)

  return api(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    },
    body: formData
  })
}

export const apiDeleteFont = id => {
  const url = GFPDF.restUrl + 'fonts/' + id

  return api(url, {
    method: 'DELETE',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    }
  })
}
