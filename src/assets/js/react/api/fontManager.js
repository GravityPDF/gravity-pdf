/* Dependencies */
import { serialize } from 'object-to-formdata';
/* APIs */
import { api, getJsonString } from './api';
/* Utilities */
import { updateURLParameter } from '../../admin/settings/common/updateURLParameter';

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
 * @property {boolean} [can_install]  - Core fonts: whether this user can download its missing files
 */

/**
 * The parts of a response the sagas read
 *
 * @param {Response} response
 *
 * @return {{ body: *, text: string, status: number, ok: boolean }} The parsed response
 *
 * @since 6.19.0
 */
const parseResponse = async (response) => {
	const text = await response.text();

	return {
		body: getJsonString(text),
		text,
		status: response.status,
		ok: response.ok,
	};
};

/**
 * A fonts endpoint URL with its scope
 *
 * @param {string} id
 * @param {string} scope
 *
 * @return {string} The URL
 *
 * @since 6.19.0
 */
const fontsUrl = (id, scope) =>
	updateURLParameter(GFPDF.restUrl + 'fonts/' + id, 'scope', scope);

/**
 * Fetch API GET request to the fonts endpoint
 *
 * @param {string} scope
 *
 * @return {{ body: *, text: string, status: number, ok: boolean }} The parsed response
 *
 * @since 6.19.0
 */
const getFonts = async (scope) =>
	parseResponse(
		await api(fontsUrl('', scope), {
			method: 'GET',
			headers: {
				'X-WP-Nonce': GFPDF.restNonce,
				Accept: 'application/json',
			},
		})
	);

/**
 * Fetch API request to obtain every font the Font Manager lists (GET)
 *
 * @return {{ body: Array<InstalledFont>, text: string, status: number, ok: boolean }} Custom, core and bundled fonts
 *
 * @since 6.0
 */
export const apiGetCustomFontList = () => getFonts('all');

/**
 * Fetch API request for why fonts are in use, which is slower than the list as it reads every form (GET)
 *
 * @return {{ body: Object<string, Array<string>>, text: string, status: number, ok: boolean }} Reasons by font ID
 *
 * @since 6.19.0
 */
export const apiGetFontUsage = () => getFonts('usage');

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

	return parseResponse(response);
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

	return parseResponse(response);
};

/**
 * Send a bodyless request about a font
 *
 * @param {string} method
 * @param {string} url
 *
 * @return {{ body: Object, text: string, status: number, ok: boolean }} The response
 *
 * @since 6.19.0
 */
const sendFontRequest = async (method, url) => {
	const response = await api(url, {
		method,
		headers: {
			'X-WP-Nonce': GFPDF.restNonce,
			Accept: 'application/json',
		},
	});

	return parseResponse(response);
};

/**
 * Fetch API request to download a core font (POST)
 *
 * @param {string} id
 *
 * @return {{ body: Object, text: string, status: number, ok: boolean }} The response, with the font's row or why it failed
 *
 * @since 6.19.0
 */
export const apiInstallFont = (id) =>
	sendFontRequest('POST', fontsUrl(id, 'core'));

/**
 * Fetch API request to delete existing font (DELETE)
 *
 * @param {string} id       - ID of the font to delete
 * @param {string} fontType - The row's `type`
 *
 * @return {{ body: Object, text: string, status: number, ok: boolean }} The response, with the server's reason when it refuses
 *
 * @since 6.0
 */
export const apiDeleteFont = (id, fontType) =>
	sendFontRequest('DELETE', fontsUrl(id, fontType));
