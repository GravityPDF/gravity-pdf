/* Redux action types */
import {
	GET_DOWNLOADED_FONTS,
	GET_DOWNLOADED_FONTS_SUCCESS,
	GET_DOWNLOADED_FONTS_ERROR,
	DELETE_DOWNLOADED_FONT,
	DELETE_DOWNLOADED_FONT_SUCCESS,
	DELETE_DOWNLOADED_FONT_ERROR,
} from '../actions/downloadedFonts';
import { RESET_SEARCH_RESULT, SEARCH_FONT_LIST } from '../actions/fontManager';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * @typedef DownloadedFontsState
 * @property {boolean}       loading    - Whether the list is loading
 * @property {Array<Object>} fonts      - The downloaded fonts
 * @property {boolean}       canDelete  - Whether the user may delete them
 * @property {string}        deletingId - The font being deleted
 * @property {string}        keyword    - The Font Manager search, which filters this list too
 * @property {Object}        msg        - `success` or `error` notice
 */
export const initialState = {
	loading: false,
	fonts: [],
	canDelete: false,
	deletingId: '',
	keyword: '',
	msg: {},
};

/**
 * The "downloadedFonts" reducer
 *
 * @param {DownloadedFontsState} state
 * @param {Object}               action
 *
 * @return {DownloadedFontsState} state
 *
 * @since 6.19.0
 */
export default function (state = initialState, action) {
	const { payload } = action;

	switch (action.type) {
		case GET_DOWNLOADED_FONTS:
			return { ...state, loading: true, msg: {} };

		case GET_DOWNLOADED_FONTS_SUCCESS:
			return {
				...state,
				loading: false,
				fonts: payload.fonts,
				canDelete: payload.can_delete,
			};

		case GET_DOWNLOADED_FONTS_ERROR:
			return { ...state, loading: false, msg: { error: payload } };

		case DELETE_DOWNLOADED_FONT:
			return { ...state, deletingId: payload.id, msg: {} };

		case DELETE_DOWNLOADED_FONT_SUCCESS:
			return {
				...state,
				deletingId: '',
				fonts: state.fonts.filter((font) => font.id !== payload.id),
				msg: { success: payload.msg },
			};

		case DELETE_DOWNLOADED_FONT_ERROR:
			return { ...state, deletingId: '', msg: { error: payload } };

		case SEARCH_FONT_LIST:
			return { ...state, keyword: payload };

		case RESET_SEARCH_RESULT:
			return { ...state, keyword: '' };
	}

	return state;
}
