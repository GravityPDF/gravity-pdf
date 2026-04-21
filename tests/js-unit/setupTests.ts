import util from 'util';
import { setAutoFreeze } from 'immer';
import '@testing-library/jest-dom';
// @ts-ignore no type declarations for css.escape
import CSS from 'css.escape'; // eslint-disable-line
import type { GFPDFGlobal } from '../../src/assets/js/react/types/global';

Object.defineProperty(global, 'TextEncoder', {
	value: util.TextEncoder,
});

/* Prevent immer from freezing state objects, which would break tests that
   mutate initialState directly before passing it to reducers. */
setAutoFreeze(false);

// setup global defaults that our tests/legacy code expect is present
window.GFPDF = {
	templateList: [{ id: 'zadani' }, { id: 'rubix' }, { id: 'focus-gravity' }],
	activeTemplate: '',
	coreFontItemPendingMessage: '%s',
	coreFontItemSuccessMessage: '%s',
	coreFontItemErrorMessage: '%s',
	noResultText:
		"It doesn't look like there are any topics related to your issue.",
	coreFontGithubError: 'Could not download Core Font list. Try again.',
	getSearchResultError: 'An error occurred. Please try again',
	userCapabilities: { administrator: true },
	// Font manager component
	fontListInstalledFonts: 'Installed Fonts',
	fontListRegular: 'Regular',
	fontListItalics: 'Italics',
	fontListBold: 'Bold',
	fontListBoldItalics: 'Bold Italics',
	fontManagerAddTitle: 'Add Font',
	fontManagerUpdateTitle: 'Update Font',
	fontListRegularRequired: 'Regular',
	searchResultEmpty: 'No fonts matching your search found.',
	fontListEmpty: 'Font list empty.',
	fontManagerFontFilesLabel: 'Font Files',
	fontManagerTemplateTooltipLabel: 'View template usage',
	addUpdateFontSuccess: 'Your font has been saved.',
	addFatalError: 'A problem occurred. Reload the page and try again.',
	fontFileInvalid:
		'Font file(s) are malformed and cannot be used with Gravity PDF',
	manage: 'Advanced',
	closeDialog: 'Close dialog',
	searchBoxResetTitle: 'Clear search.',
	fontManagerTitle: 'Font Manager',
	fontUserDefinedGroup: 'User-Defined Fonts',
	fontManagerRequiredLabel: '(required)',
} as unknown as GFPDFGlobal;

(
	window as unknown as { gfpdf_migration_multisite_ids: unknown[] }
).gfpdf_migration_multisite_ids = [];
