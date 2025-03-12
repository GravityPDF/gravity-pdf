/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Toggle show or hide update font panel
 *
 * @param { Function } navigate
 * @param { string }   fontId
 * @param { string }   pathname
 *
 * @return { Function } removeClass
 *
 * @since 6.0
 */
export function toggleUpdateFont(navigate, fontId, pathname) {
	const editFontColumn = document.querySelector('.update-font');

	if (fontId) {
		if (pathname?.substr(pathname.lastIndexOf('/') + 1) === fontId) {
			return removeClass(editFontColumn, navigate, pathname);
		}

		return addClass(editFontColumn, navigate, fontId);
	}

	return removeClass(editFontColumn, navigate, pathname);
}

/**
 * Removes show class and navigates to /fontmanager/
 *
 * @param { HTMLElement } editFontColumn
 * @param { Function }    navigate
 * @param { string }      pathname
 *
 * @return { * } navigate or do nothing
 */
export function removeClass(editFontColumn, navigate, pathname) {
	editFontColumn.classList.remove('show');

	/* Avoid Warning: Hash history cannot PUSH the same path */
	if (pathname === '/fontmanager/') {
		return;
	}

	return navigate('/fontmanager/');
}

/**
 * Adds show class and navigates to /fontmanager/<id>
 *
 * @param { HTMLElement } editFontColumn
 * @param { Function }    navigate
 * @param { string }      fontId
 *
 * @return { Function } navigate
 *
 * @since 6.0
 */
export function addClass(editFontColumn, navigate, fontId) {
	editFontColumn.classList.add('show');

	return navigate('/fontmanager/' + fontId);
}
