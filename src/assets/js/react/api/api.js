/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Wrapper for the fetch() API which return a promise response
 *
 * @param { string }                         url     url of the request
 * @param { Object }                         init    options to be passed on fetch request
 * @param { {
 *   useNativeResponse?: boolean
 *   useNativeErrorResponse?: boolean
 * }= } options used when you need a custom logic for handling success or error responses
 *
 * @return { Promise<*> } response - it might be either a parsed response or a raw response
 *
 * @since 6.0
 */
export const api = async (url, init, options) => {
	const response = await fetch(url, init);

	if (!response.ok) {
		if (options?.useNativeErrorResponse) {
			return response;
		}

		const parsedResponse = await response.json();

		throw new Error(parsedResponse.error);
	}

	if (options?.useNativeResponse) {
		return response;
	}

	return await response.json();
};
