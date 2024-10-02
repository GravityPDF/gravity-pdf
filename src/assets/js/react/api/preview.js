import { api } from './api'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * A cache of template schema data, grouped by form
 * @type {object}
 */
const templateSchema = {}

/**
 * Get template schema data
 *
 * @param {int} formId
 * @param {string} template
 * @returns {object} Template Schema data
 *
 * @since 7.0
 */
export async function getTemplateSchema (formId, template) {
  // add formId key to cache
  if (!templateSchema[formId]) {
    templateSchema[formId] = {}
  }

  // return cached schema
  if (templateSchema[formId][template]) {
    return templateSchema[formId][template]
  }

  const url = GFPDF.restUrl + 'form/' + encodeURIComponent(formId) + '/schema/?template=' + encodeURIComponent(template)
  const response = await api(url, {
    method: 'GET',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    }
  })

  try {
    if (!response.ok) {
      throw new Error(await response.json())
    }

    templateSchema[formId][template] = await response.json()

    return templateSchema[formId][template]
  } catch (e) {
    console.error(e)
  }
}

/**
 * Generate a PDF Preview using the defined PDF settings
 *
 * @param {FormData} formData
 * @returns {Blob|null}
 *
 * @since 7.0
 */
export async function getPdfPreview (formData) {
  const url = GFPDF.restUrl + 'form/' + encodeURIComponent(formData.get('form')) + '/preview'
  const response = await api(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': GFPDF.restNonce
    },
    body: formData
  })

  try {
    if (!response.ok) {
      throw new Error(await response.json())
    }

    return await response.blob()
  } catch (e) {
    console.error(e)
  }
}
