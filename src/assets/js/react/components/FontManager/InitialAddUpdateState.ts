/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export interface FontStyles {
	regular: string | File;
	italics: string | File;
	bold: string | File;
	bolditalics: string | File;
}

export interface AddUpdateFontState {
	id: string;
	label: string;
	fontStyles: FontStyles;
	validateLabel: boolean;
	validateRegular: boolean;
	disableUpdateButton: boolean;
}

const initialState: AddUpdateFontState = {
	id: '',
	label: '',
	fontStyles: {
		regular: '',
		italics: '',
		bold: '',
		bolditalics: '',
	},
	validateLabel: true,
	validateRegular: true,
	disableUpdateButton: false,
};

export default initialState;
