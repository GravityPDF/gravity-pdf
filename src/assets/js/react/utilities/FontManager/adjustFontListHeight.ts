/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * This function is used to auto adjust font list column height to avoid
 * overlapping display of the update font panel
 *
 * @since 6.0
 */
export function adjustFontListHeight(): void {
	const fontListColumn =
		document.querySelector<HTMLElement>('.font-list-column');
	const updateFont = document.querySelector<HTMLElement>('.update-font.show');

	if (fontListColumn && updateFont) {
		fontListColumn.style.height =
			window.getComputedStyle(updateFont).height;
	}
}
