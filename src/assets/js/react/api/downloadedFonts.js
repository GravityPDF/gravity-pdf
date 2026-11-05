/* APIs */
import { api, getJsonString } from './api';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * @typedef {Object} DownloadedFont
 * @property {string}                                               id           - mPDF font key
 * @property {string}                                               label        - Display name
 * @property {Array<{ style: string, file: string, size: number }>} files        - Faces on disk
 * @property {string}                                               size_label   - Size on disk, formatted
 * @property {Array<string>}                                        in_use       - Why it can't be deleted
 * @property {boolean}                                              auto_install - Whether it downloads again on its own
 */

/**
 * Send a request to the downloaded fonts endpoint
 *
 * @param {string} method
 * @param {string} path
 *
 * @return {Promise<{ body: Object, status: number, ok: boolean }>} The parsed response
 *
 * @since 6.19.0
 */
const request = async (method, path) => {
	const response = await api(GFPDF.restUrl + 'fonts/downloaded' + path, {
		method,
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
	});

	const text = await response.text();

	return {
		body: getJsonString(text),
		status: response.status,
		ok: response.ok,
	};
};

/**
 * Get the downloaded fonts (GET)
 *
 * @return {Promise<{ body: { fonts: Array<DownloadedFont>, can_delete: boolean }, status: number, ok: boolean }>} The list
 *
 * @since 6.19.0
 */
export const apiGetDownloadedFonts = () => request('GET', '');

/**
 * Delete a downloaded font's files (DELETE)
 *
 * @param {string} id - mPDF font key
 *
 * @return {Promise<{ body: Object, status: number, ok: boolean }>} The files removed, or the error
 *
 * @since 6.19.0
 */
export const apiDeleteDownloadedFont = (id) =>
	request('DELETE', '/' + encodeURIComponent(id));
