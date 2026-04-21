/* Dependencies */
import { api, getJsonString } from './api';
/* Types */
import { ApiResponse } from '../types';

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

export async function apiGetFilesFromGitHub(): Promise<
	ApiResponse<CoreFontItem[]>
> {
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
	const body = getJsonString(text) as CoreFontItem[];

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
}

export async function apiPostDownloadFonts(
	file: string
): Promise<ApiResponse<unknown>> {
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
