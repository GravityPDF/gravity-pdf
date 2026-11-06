/* Dependencies */
import { serialize } from 'object-to-formdata';
/* APIs */
import { api, getJsonString } from './api';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * @typedef {Object} FontItem
 * @property {string} id          - Unique font identifier
 * @property {string} font_name   - Display name
 * @property {string} regular     - Path to regular variant file
 * @property {string} italics     - Path to italics variant file
 * @property {string} bold        - Path to bold variant file
 * @property {string} bolditalics - Path to bold-italics variant file
 */

/**
 * @typedef {FontItem} InstalledFont
 * @property {string}  type           - `custom`, `core` or `bundled`
 * @property {boolean} can_delete     - Whether this user can delete it when it's unused
 * @property {boolean} [auto_install] - Core fonts: whether it downloads again on its own
 */

/**
 * Fetch API request to obtain every font the Font Manager lists (GET)
 *
 * @return {{ body: Array<InstalledFont>, text: string, status: number, ok: boolean }} Custom, core and bundled fonts
 *
 * @since 6.0
 */
export const apiGetCustomFontList = async () => {
	const url = GFPDF.restUrl + 'fonts/installed';

	const response = await api(url, {
		method: 'GET',
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
	});

	const text = await response.text();
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
};

/**
 * Fetch API request for why fonts are in use, which is slower than the list as it reads every form (GET)
 *
 * @return {{ body: Object<string, Array<string>>, text: string, status: number, ok: boolean }} Reasons by font ID
 *
 * @since 6.19.0
 */
export const apiGetFontUsage = async () => {
	const response = await api(GFPDF.restUrl + 'fonts/installed/usage', {
		method: 'GET',
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
	});

	const text = await response.text();

	return {
		body: getJsonString(text),
		text,
		status: response.status,
		ok: response.ok,
	};
};

/**
 * Fetch API request to add new font (POST)
 *
 * @param {{ label: string, regular: string|File, italics: string|File, bold: string|File, bolditalics: string|File }} font
 *
 * @return {{ body: FontItem, text: string, status: number, ok: boolean }} The newly created font
 *
 * @since 6.0
 */
export const apiAddFont = async (font) => {
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
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
};

/**
 * Fetch API request to edit font details (POST)
 *
 * @param {Object}                                                                                                     params
 * @param {string}                                                                                                     params.id   - ID of the font to update
 * @param {{ label: string, regular: string|File, italics: string|File, bold: string|File, bolditalics: string|File }} params.font - Only changed variants are included
 *
 * @return {{ body: FontItem, text: string, status: number, ok: boolean }} The updated font
 *
 * @since 6.0
 */
export const apiEditFont = async ({ id, font }) => {
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
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
};

/**
 * Fetch API request to delete existing font (DELETE)
 *
 * @param {string} id       - ID of the font to delete
 * @param {string} fontType - The row's `type`
 *
 * @return {{ body: Object, status: number, ok: boolean }} The response, with the server's reason when it refuses
 *
 * @since 6.0
 */
export const apiDeleteFont = async (id, fontType) => {
	const path = fontType === 'core' ? 'fonts/downloaded/' : 'fonts/';

	const response = await api(GFPDF.restUrl + path + id, {
		method: 'DELETE',
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
