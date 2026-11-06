/* Redux action types */
export const GET_DOWNLOADED_FONTS = 'GET_DOWNLOADED_FONTS';
export const GET_DOWNLOADED_FONTS_SUCCESS = 'GET_DOWNLOADED_FONTS_SUCCESS';
export const GET_DOWNLOADED_FONTS_ERROR = 'GET_DOWNLOADED_FONTS_ERROR';
export const DELETE_DOWNLOADED_FONT = 'DELETE_DOWNLOADED_FONT';
export const DELETE_DOWNLOADED_FONT_SUCCESS = 'DELETE_DOWNLOADED_FONT_SUCCESS';
export const DELETE_DOWNLOADED_FONT_ERROR = 'DELETE_DOWNLOADED_FONT_ERROR';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * Request the downloaded fonts
 *
 * @return {{ type: string }} action object
 *
 * @since 6.19.0
 */
export const getDownloadedFonts = () => ({
	type: GET_DOWNLOADED_FONTS,
});

/**
 * Delete a downloaded font
 *
 * @param {{ id: string, label: string }} font
 *
 * @return {{ type: string, payload: { id: string, label: string } }} action object
 *
 * @since 6.19.0
 */
export const deleteDownloadedFont = (font) => ({
	type: DELETE_DOWNLOADED_FONT,
	payload: font,
});
