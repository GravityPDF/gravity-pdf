/* Dependencies */
import { api, getJsonString } from './api'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Do AJAX call
 *
 * @returns {Promise}
 *
 * @since 5.2
 */
export async function apiPostUpdateSelectBox () {
  const formData = new window.FormData()
  formData.append('action', 'gfpdf_get_template_options')
  formData.append('nonce', GFPDF.ajaxNonce)

  const response = await api(GFPDF.ajaxUrl, {
    method: 'POST',
    body: formData
  })

  const text = await response.text()

  return {
    body: text,
    text,
    status: response.status,
    ok: response.ok
  }
}

/**
 * Do AJAX call
 *
 * @param {String} templateId
 *
 * @returns {Promise}
 *
 * @since 5.2
 */
export async function apiPostTemplateProcessing (templateId) {
  const formData = new window.FormData()
  formData.append('action', 'gfpdf_delete_template')
  formData.append('nonce', GFPDF.ajaxNonce)
  formData.append('id', templateId)

  const response = await api(GFPDF.ajaxUrl, {
    method: 'POST',
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
 * Do AJAX call
 *
 * @param {Object} file
 * @param {String} filename
 *
 * @returns {Promise}
 *
 * @since 5.2
 */
export async function apiPostTemplateUploadProcessing (file, filename) {
  const formData = new window.FormData()
  formData.append('action', 'gfpdf_upload_template')
  formData.append('nonce', GFPDF.ajaxNonce)
  formData.append('template', file, filename)

  const response = await api(GFPDF.ajaxUrl, {
    method: 'POST',
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
