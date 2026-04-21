/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

import { NavigateFunction } from 'react-router-dom';

export function toggleUpdateFont(
	navigate: NavigateFunction,
	fontId = '',
	pathname = ''
): void {
	const editFontColumn = document.querySelector<HTMLElement>('.update-font');

	if (fontId) {
		if (pathname?.substr(pathname.lastIndexOf('/') + 1) === fontId) {
			return removeClass(editFontColumn, navigate, pathname);
		}

		return addClass(editFontColumn, navigate, fontId);
	}

	return removeClass(editFontColumn, navigate, pathname);
}

export function removeClass(
	editFontColumn: HTMLElement | null,
	navigate: NavigateFunction,
	pathname = ''
): void {
	editFontColumn?.classList.remove('show');

	/* Avoid Warning: Hash history cannot PUSH the same path */
	if (pathname === '/fontmanager/') {
		return;
	}

	navigate('/fontmanager/');
}

export function addClass(
	editFontColumn: HTMLElement | null,
	navigate: NavigateFunction,
	fontId: string
): void {
	editFontColumn?.classList.add('show');

	navigate('/fontmanager/' + fontId);
}
