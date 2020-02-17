import request from 'superagent'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Do AJAX call
 *
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostUpdateSelectBox () {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_get_template_options')
    .field('nonce', GFPDF.ajaxNonce)
}

/**
 * Do AJAX call
 *
 * @param {String} templateId
 *
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostTemplateProcessing (templateId) {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_delete_template')
    .field('nonce', GFPDF.ajaxNonce)
    .field('id', templateId)
}

/**
 * Do AJAX call
 *
 * @param {{file: Object, filename: String}}
 *
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostTemplateUploadProcessing (file, filename) {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_upload_template')
    .field('nonce', GFPDF.ajaxNonce)
    .attach('template', file, filename)
}
