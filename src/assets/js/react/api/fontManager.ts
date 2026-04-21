/* Dependencies */
import { serialize } from 'object-to-formdata';
/* APIs */
import { api, getJsonString } from './api';
/* Types */
import { FontItem, FontFormData, ApiResponse } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export const apiGetCustomFontList = async (): Promise<
	ApiResponse<FontItem[]>
> => {
	const url = GFPDF.restUrl + 'fonts/';

	const response = await api(url, {
		method: 'GET',
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
	});

	const text = await response.text();
	const body = getJsonString(text) as FontItem[];

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
};

export const apiAddFont = async (
	font: FontFormData
): Promise<ApiResponse<FontItem>> => {
	const url = GFPDF.restUrl + 'fonts/';
	const formData = serialize(font);

	const response = await api(url, {
		method: 'POST',
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
		body: formData,
	});

	const text = await response.text();
	const body = getJsonString(text) as FontItem;

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
};

export const apiEditFont = async ({
	id,
	font,
}: {
	id: string;
	font: Partial<FontFormData>;
}): Promise<ApiResponse<FontItem>> => {
	const url = GFPDF.restUrl + 'fonts/' + id;
	const data = { ...font };
	const formData = serialize(data);

	const response = await api(url, {
		method: 'POST',
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
		body: formData,
	});

	const text = await response.text();
	const body = getJsonString(text) as FontItem;

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
};

export const apiDeleteFont = (id: string): Promise<Response> => {
	const url = GFPDF.restUrl + 'fonts/' + id;

	return api(url, {
		method: 'DELETE',
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
		},
	});
};
