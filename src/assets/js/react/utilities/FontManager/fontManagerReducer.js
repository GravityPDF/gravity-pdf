/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * @typedef { Object } Font
 * @property { string } id          - the font id
 * @property { string } font_name   - the name of the font
 * @property { string } regular     - file name for regular
 * @property { string } italics     - file name for italics
 * @property { string } bold        - file name for bold
 * @property { string } bolditalics - file name for bold italics
 */

/**
 * This function is used to generate a new array by mapping
 * redux store "fontList" or "searchResult" state with the
 * current payload data
 *
 * @param { Array<Font> } data
 * @param { Object }      payload
 * @param { Font }        payload.font
 *
 * @return { Array<Font> } data
 *
 * @since 6.0
 */
export function findAndUpdate(data, payload) {
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

/**
 * This function is used to filter or remove payload data from the current
 * redux store "fontList" or "searchResult" state
 *
 * @param { Array<Font> } data
 * @param { string }      payload
 *
 * @return { Array<Font> } data
 *
 * @since 6.0
 */
export function findAndRemove(data, payload) {
	const list = [...data];

	return list.filter((font) => font.id !== payload);
}

/**
 * Utility function to trim font file name
 *
 * @param { string } key
 *
 * @return { string } trimmed font file name
 *
 * @since 6.0
 */
export function reduceFontFileName(key) {
	return key.substr(key.lastIndexOf('/') + 1);
}

/**
 * This function is used to check string if it's a match with the current keyword
 *
 * @param { string } font
 * @param { string } keyword
 *
 * @return { boolean } true if font includes keyword otherwise false
 *
 * @since 6.0
 */
export function checkFontListIncludes(font, keyword) {
	return font.replace('.ttf', '').toLowerCase().includes(keyword);
}

/**
 * This function is used to clear/reset redux store "msg" state
 *
 * @param { Object } payload
 * @param { string } payload.success
 * @param { Object } payload.error
 * @param { string } payload.error.addFont
 *
 * @return { Object } message
 *
 * @since 6.0
 */
export function clearMsg(payload) {
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
