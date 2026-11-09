import util from 'util';
import Enzyme from 'enzyme';
import Adapter from '@cfaester/enzyme-adapter-react-18';
import CSS from 'css.escape'; // eslint-disable-line

Object.defineProperty(global, 'TextEncoder', {
	value: util.TextEncoder,
});

Enzyme.configure({
	adapter: new Adapter(),
	disableLifecycleMethods: true,
});

// setup global defaults that our tests/legacy code expect is present
window.GFPDF = {
	templateList: [{ id: 'zadani' }, { id: 'rubix' }, { id: 'focus-gravity' }],
	activeTemplate: '',
	noResultText:
		"It doesn't look like there are any topics related to your issue.",
	getSearchResultError: 'An error occurred. Please try again',
	licenseDeactivationError:
		'An error occurred and your license key may not have been correctly deactivated. Login to your GravityPDF.com account and check if your site has been unlinked from the key.',
	userCapabilities: { administrator: true },
	canManageTemplates: true,
	templateManageNotice: '',
	// Template uploader
	problemWithTheUpload: 'genericError',
	uploadInvalidNotZipFile: 'notZip',
	uploadInvalidExceedsFileSizeLimit: 'tooBig',
	templateSuccessfullyInstalled: 'installed',
	templateSuccessfullyUpdated: 'updated',
	templateSuccessfullyInstalledUpdated: 'successText',
	templateUploadDropzone: 'dropzoneText',
	templateUploadInProgress: 'uploading',
	templateUploadMaxSize: '1000',
	templateDocsLink: 'docsLink',
	// Font manager component
	fontListSize: 'Size',
	fontListInUse: 'In use',
	fontListNotInUse: 'Not in use',
	fontManagerStyle: '%d style',
	fontManagerStyles: '%d styles',
	active: 'Active',
	fontManagerSetActive: 'Set as active',
	fontManagerTypeCustom: 'Custom',
	fontManagerTypeCore: 'Core',
	fontManagerTypeBundled: 'Bundled',
	fontManagerFontInstalled: '%d font installed',
	fontManagerFontsInstalled: '%d fonts installed',
	fontManagerPreviewText: 'Sample',
	fontManagerFontJustInstalled: '%s installed.',
	fontManagerInstall: 'Install',
	fontManagerPreviewUnavailable: 'Unavailable',
	fontManagerPreviewUnsupported: 'Unsupported',
	fontManagerPreviewFailed: 'Failed',
	fontsUrl: 'http://x/fonts/',
	fontManagerFontDeleted: '%s deleted.',
	delete: 'Delete',
	fontManagerDeleteTitle: 'Delete font?',
	fontManagerNoFile: 'No .ttf file added',
	fontManagerDropFile: 'Drop .ttf to upload',
	fontManagerUpload: '+ Upload',
	fontManagerReplace: 'Replace',
	fontManagerDeleteStyle: 'Delete %s',
	fontListRegular: 'Regular',
	fontListItalics: 'Italic',
	fontListBold: 'Bold',
	fontListBoldItalics: 'Bold Italic',
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
};

window.gfpdf_migration_multisite_ids = [];
