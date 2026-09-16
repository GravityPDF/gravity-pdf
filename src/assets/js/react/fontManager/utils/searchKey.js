/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * The cache key one page of a source search is filed under
 *
 * @param {string} source
 * @param {Object} query  The `s` / `category` / `subset` / `coverage` / `page` params
 *
 * @return {string} The key
 *
 * @since 7.0
 */
export function searchKey(source, query) {
	const parts = Object.keys(query)
		.sort()
		.filter((name) => query[name] !== '' && query[name] !== undefined)
		.map((name) => `${name}=${query[name]}`);

	return [source, ...parts].join('&');
}
