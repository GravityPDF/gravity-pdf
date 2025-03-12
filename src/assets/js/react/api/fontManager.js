/* Dependencies */
import { serialize } from 'object-to-formdata';
/* APIs */
import { api } from './api';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Fetch API request to obtain custom font list (GET)
 *
 * @return { Promise<*> } response
 *
 * @since 6.0
 */
export const apiGetCustomFontList = async () => {
	const url = GFPDF.restUrl + 'fonts/';

	return await api(
		url,
		{
			method: 'GET',
			headers: {
				'X-WP-Nonce': GFPDF.restNonce,
			},
		},
		{
			useNativeErrorResponse: true,
		}
	);
};

/**
 * Fetch API request to add new font (POST)
 *
 * @param { Object } font
 *
 * @return { Promise<Object> } response
 *
 * @since 6.0
 */
export const apiAddFont = async (font) => {
	const url = GFPDF.restUrl + 'fonts/';
	const formData = serialize(font);

	return await api(
		url,
		{
			method: 'POST',
			headers: {
				'X-WP-Nonce': GFPDF.restNonce,
			},
			body: formData,
		},
		{
			useNativeErrorResponse: true,
		}
	);
};

/**
 * Fetch API request to edit font details (POST)
 *
 * @param { Object } args
 * @param { string } args.id
 * @param { Object } args.font
 *
 * @return { Promise<Object> } response
 *
 * @since 6.0
 */
export const apiEditFont = async ({ id, font }) => {
	const url = GFPDF.restUrl + 'fonts/' + id;
	const data = { ...font };
	const formData = serialize(data);

	return await api(
		url,
		{
			method: 'POST',
			headers: {
				'X-WP-Nonce': GFPDF.restNonce,
			},
			body: formData,
		},
		{
			useNativeErrorResponse: true,
		}
	);
};

/**
 * Fetch API request to delete existing font (DELETE)
 *
 * @param { string } id
 *
 * @return { Promise<Object>} response
 *
 * @since 6.0
 */
export const apiDeleteFont = async (id) => {
	const url = GFPDF.restUrl + 'fonts/' + id;

	const res = await api(
		url,
		{
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': GFPDF.restNonce,
			},
		},
		{
			useNativeErrorResponse: true,
			useNativeResponse: true,
		}
	);

	return res;
};
