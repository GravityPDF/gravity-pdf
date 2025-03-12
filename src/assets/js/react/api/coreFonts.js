/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

import { api } from './api';

/**
 * Do AJAX call
 *
 * @return { Object } response
 *
 * @since 5.2
 */
export async function apiGetFilesFromGitHub() {
	const res = await api(GFPDF.pluginUrl + 'build/payload/core-fonts.json', {
		method: 'GET',
		headers: {
			Accept: 'application/json',
		},
	});

	return res;
}

/**
 * Do AJAX call
 *
 * @param { string } file
 *
 * @return { Promise<*> } response
 *
 * @since 5.2
 */
export function apiPostDownloadFonts(file) {
	const formData = new FormData();
	formData.append('action', 'gfpdf_save_core_font');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('font_name', file);

	return api(
		GFPDF.ajaxUrl,
		{
			method: 'POST',
			body: formData,
		},
		{
			useNativeErrorResponse: true,
		}
	);
}
