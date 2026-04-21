import { TemplateItem } from './index';

declare global {
	interface Window {
		GFPDF: GFPDFGlobal;
	}

	// eslint-disable-next-line no-var
	var GFPDF: GFPDFGlobal;
}

export interface GFPDFGlobal {
	/* WordPress REST API */
	restUrl: string;
	restNonce: string;
	ajaxUrl: string;
	ajaxNonce: string;

	/* Plugin info */
	pluginUrl: string;
	pdfWorkingDir: string;
	version: string;
	currentVersion: string;

	/* Templates */
	templateList: TemplateItem[];
	activeTemplate: string;
	activeDefaultTemplate: string;
	currentTemplate: string;
	installedPdfs: string;
	userCapabilities: Record<string, boolean | string | undefined>;
	requiresGravityPdfVersion: string;

	/* UI helpers */
	spinnerAlt: string;
	spinnerUrl: string;

	/* Generic labels */
	cancel: string;
	closeDialog: string;
	delete: string;
	details: string;
	group: string;
	manage: string;
	manageTemplates: string;
	select: string;
	tags: string;
	template: string;
	templateDetails: string;

	/* Template UI strings */
	addFatalError: string;
	addNewTemplate: string;
	couldNotDeleteTemplate: string;
	doYouWantToDeleteTemplate: string;
	noResultText: string;
	searchBoxPlaceHolderText: string;
	searchBoxResetTitle: string;
	searchBoxSubmitTitle: string;
	searchResultEmpty: string;
	searchTemplatePlaceholder: string;
	showNextTemplate: string;
	showPreviousTemplate: string;
	templateInstallInstructions: string;
	templateNotCompatibleWithGravityPdfVersion: string;
	templateSuccessfullyInstalled: string;
	templateSuccessfullyInstalledUpdated: string;
	templateSuccessfullyUpdated: string;
	problemWithTheUpload: string;
	uploadInvalidExceedsFileSizeLimit: string;
	uploadInvalidNotZipFile: string;

	/* Font Manager strings */
	fontManagerTitle: string;
	fontManagerAddTitle: string;
	fontManagerUpdateTitle: string;
	fontManagerAddDesc: string;
	fontManagerUpdateDesc: string;
	fontManagerFontNameLabel: string;
	fontManagerFontNameDesc: string;
	fontManagerFontNameValidationError: string;
	fontManagerFontFilesLabel: string;
	fontManagerFontFilesDesc: string;
	fontManagerFontFileRequiredRegular: string;
	fontManagerRequiredLabel: string;
	fontManagerCancelButtonText: string;
	fontManagerSearchPlaceHolder: string;
	fontManagerTemplateTooltipLabel: string;
	fontManagerTemplateTooltipDesc: string;
	fontManagerAddFontAriaLabel: string;
	fontManagerUpdateFontAriaLabel: string;
	fontManagerDeleteFontAriaLabel: string;
	fontManagerDeleteFontConfirmation: string;
	fontManagerSelectFontAriaLabel: string;
	fontUserDefinedGroup: string;
	fontListRegular: string;
	fontListItalics: string;
	fontListBold: string;
	fontListBoldItalics: string;
	fontListInstalledFonts: string;
	fontListEmpty: string;
	fontFileInvalid: string;
	fontFileMissing: string;
	addUpdateFontError: string;
	addUpdateFontSuccess: string;

	/* Core Fonts strings */
	coreFontAriaLabel: string;
	coreFontCounter: string;
	coreFontError: string;
	coreFontGithubError: string;
	coreFontItemErrorMessage: string;
	coreFontItemPendingMessage: string;
	coreFontItemSuccessMessage: string;
	coreFontRetry: string;
	coreFontSuccess: string;
}
