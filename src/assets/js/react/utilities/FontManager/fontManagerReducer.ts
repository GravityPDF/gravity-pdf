/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

import { FontItem, FontManagerMsg } from '../../types';

export function findAndUpdate(
	data: FontItem[],
	payload: { font: FontItem }
): FontItem[] {
	const list = [...data];

	return list.map((font) => {
		if (font.id === payload.font.id) {
			font.font_name = payload.font.font_name;
			font.regular = payload.font.regular;
			font.italics = payload.font.italics;
			font.bold = payload.font.bold;
			font.bolditalics = payload.font.bolditalics;
		}

		return font;
	});
}

export function findAndRemove(data: FontItem[], payload: string): FontItem[] {
	const list = [...data];

	return list.filter((font) => font.id !== payload);
}

export function reduceFontFileName(key: string): string {
	return key.substr(key.lastIndexOf('/') + 1);
}

export function checkFontListIncludes(font: string, keyword: string): boolean {
	return font.replace('.ttf', '').toLowerCase().includes(keyword);
}

export function clearMsg(payload: FontManagerMsg): FontManagerMsg {
	const msg = { ...payload };

	/* Clear previous success msg */
	if (msg.success) {
		delete msg.success;
	}

	/* Clear previous addFont error msg */
	if (msg.error && msg.error.addFont) {
		delete msg.error.addFont;
	}

	return msg;
}
