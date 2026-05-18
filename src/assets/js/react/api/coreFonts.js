/* Dependencies */
import { api, getJsonString } from './api';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Do AJAX call
 *
 * @return {{ body: Object, text: string, status: number, ok: boolean }} Parsed core-fonts.json payload
 *
 * @since 5.2
 */
export async function apiGetFilesFromGitHub() {
	const response = await api(
		GFPDF.pluginUrl + 'build/payload/core-fonts.json',
		{
			method: 'GET',
			headers: {
				Accept: 'application/json',
			},
		}
	);

	const text = await response.text();
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
}

/**
 * Do AJAX call
 *
 * @param {string} file
 * @return {{ body: Object, text: string, status: number, ok: boolean }} Server response for the font download
 *
 * @since 5.2
 */
export async function apiPostDownloadFonts(file) {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_save_core_font');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('font_name', file);

	const response = await api(GFPDF.ajaxUrl, {
		method: 'POST',
		body: formData,
	});

	const text = await response.text();
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
}
