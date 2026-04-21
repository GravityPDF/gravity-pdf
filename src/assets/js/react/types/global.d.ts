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
	currentVersion: string;

	/* Templates — runtime config (not translated) */
	templateList: TemplateItem[];
	activeTemplate: string;
	activeDefaultTemplate: string;
	userCapabilities: Record<string, boolean | string | undefined>;

	/* Admin bundle (admin.min.js) — migrated to __() separately */
	spinnerAlt: string;
	spinnerUrl: string;
	letsGoCreateOne: string;
	thisFormHasNoPdfs: string;
	pdfDeleteWarning: string;
}
