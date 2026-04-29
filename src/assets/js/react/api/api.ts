/* Dependencies */
import { __ } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export const getJsonString = (str: string): unknown => {
	for (const character of ['{', '[']) {
		let testStr = str;
		const index = testStr.indexOf(character);
		if (index > 0) {
			testStr = testStr.slice(index);
		}

		try {
			return JSON.parse(testStr);
		} catch (e) {}
	}

	// eslint-disable-next-line no-console
	console.error('Invalid API response', str);

	return {
		error: __(
			'A problem occurred. Reload the page and try again.',
			'gravity-pdf'
		),
	};
};
