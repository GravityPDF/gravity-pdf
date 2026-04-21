/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

import { FontItem } from '../../types';

export function associatedFontManagerSelectBox(
	fontList: FontItem[],
	id = ''
): void {
	const fontManagerSelectBox = document.querySelector<HTMLSelectElement>(
		'.gfpdf-font-manager select'
	);

	if (!fontManagerSelectBox) {
		return;
	}

	const selectedValue = fontManagerSelectBox.value;
	const definedFontsOptgroup = document.querySelector(
		// eslint-disable-next-line no-undef
		'optgroup[label="' + CSS.escape(GFPDF.fontUserDefinedGroup) + '"]'
	);

	// Remove the entire User-Defined Font group if it exists
	if (definedFontsOptgroup) {
		definedFontsOptgroup.remove();
	}

	// Do nothing if no custom fonts
	if (fontList.length === 0) {
		return;
	}

	// Build our new custom font group
	const optgroup = document.createElement('optgroup');
	optgroup.setAttribute('label', GFPDF.fontUserDefinedGroup);

	/* Build User-Defined Fonts optgroup list */
	fontList.map((font) => {
		const option = document.createElement('option');
		option.text = font.font_name;
		option.value = font.id;

		return optgroup.appendChild(option);
	});

	fontManagerSelectBox.insertBefore(
		optgroup,
		fontManagerSelectBox.childNodes[0]
	);

	fontManagerSelectBox.value = !id ? selectedValue : id;
}
