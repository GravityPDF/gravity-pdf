/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Open a font, or close the open one. The route decides which panel shows.
 *
 * @param { Function } navigate
 * @param { string }   fontId
 * @param { string }   pathname
 *
 * @return { * } navigate or do nothing
 *
 * @since 6.0
 */
export function toggleUpdateFont(navigate, fontId, pathname) {
	if (fontId) {
		return navigate('/fontmanager/' + fontId);
	}

	/* Avoid Warning: Hash history cannot PUSH the same path */
	if (pathname === '/fontmanager/') {
		return;
	}

	return navigate('/fontmanager/');
}
