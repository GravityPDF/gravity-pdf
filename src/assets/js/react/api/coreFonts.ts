/* Dependencies */
import apiFetch from '@wordpress/api-fetch';
/* APIs */
import { getJsonString } from './api';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

interface CoreFontItem {
	name: string;
	[key: string]: unknown;
}

export async function apiGetFilesFromGitHub(): Promise<CoreFontItem[]> {
	const response = await apiFetch({
		url: GFPDF.pluginUrl + 'build/payload/core-fonts.json',
		method: 'GET',
		parse: false,
	});
	if (!response.ok) {
		throw new Error(`Request failed: ${response.status}`);
	}
	const text = await response.text();
	return getJsonString(text) as CoreFontItem[];
}

export async function apiPostDownloadFonts(file: string): Promise<unknown> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_save_core_font');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('font_name', file);

	const response = await apiFetch({
		url: GFPDF.ajaxUrl,
		method: 'POST',
		body: formData,
		parse: false,
	});
	if (!response.ok) {
		throw new Error(`Request failed: ${response.status}`);
	}
	const text = await response.text();
	const body = getJsonString(text);
	if (!body) {
		throw new Error('Font download failed');
	}
	return body;
}
