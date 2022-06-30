/* Dependencies */
import request from 'superagent/dist/superagent.min'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Do AJAX call
 *
 * @returns {{method.get}}
 *
 * @since 5.2
 */
export function apiGetFilesFromGitHub () {
  return request
    .get(GFPDF.pluginUrl + 'dist/payload/core-fonts.json')
    .accept('application/json')
    .type('json')
    .parse(response => JSON.parse(response.text))
}

/**
 * Do AJAX call
 *
 * @param file
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostDownloadFonts (file) {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_save_core_font')
    .field('nonce', GFPDF.ajaxNonce)
    .field('font_name', file)
}
