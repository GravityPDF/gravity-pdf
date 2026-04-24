export interface FontItem {
	id: string;
	font_name: string;
	regular: string;
	italics: string;
	bold: string;
	bolditalics: string;
}

export interface FontFormData {
	label: string;
	regular: string | File;
	italics: string | File;
	bold: string | File;
	bolditalics: string | File;
}

export interface TemplateItem {
	id: string;
	template: string;
	description: string;
	author: string;
	group: string;
	version: string;
	required_pdf_version: string;
	tags: string;
	screenshot: string;
	new?: boolean;
	compatible?: boolean;
	error?: string;
	long_error?: string;
	long_message?: string;
	[key: string]: unknown;
}

export interface ApiResponse<T = unknown> {
	body: T;
	text: string;
	status: number;
	ok: boolean;
}

export interface FontManagerMsg {
	success?: {
		addFont?: string;
	};
	error?: {
		fontList?: string;
		addFont?: string | Record<string, string>;
		deleteFont?: string;
		fontValidationError?: string;
	};
}

export interface FontFormState {
	id?: string;
	label: string;
	fontStyles: {
		regular: string | File;
		italics: string | File;
		bold: string | File;
		bolditalics: string | File;
	};
	validateLabel: boolean;
	validateRegular: boolean;
	disableUpdateButton: boolean;
}

export interface FontManagerState {
	loading: boolean;
	addFontLoading: boolean;
	deleteFontLoading: boolean;
	fontList: FontItem[];
	searchResult: FontItem[] | null;
	selectedFont: string;
	msg: FontManagerMsg;
	addFont: FontFormState;
	updateFont: FontFormState;
}

export interface ConsoleLine {
	status: 'success' | 'error' | 'pending';
	message: string;
}

export interface CoreFontState {
	buttonClicked: boolean;
	fontList: string[];
	console: Record<string, ConsoleLine>;
	retry: string[];
	getFilesFromGitHubFailed: string;
	requestDownload: string;
	downloadCounter: number;
}

export interface TemplateState {
	list: TemplateItem[];
	activeTemplate: string;
	search: string;
	updateSelectBoxText: string;
	templateProcessing: string;
	templateUploadProcessingSuccess: Record<string, unknown>;
	templateUploadProcessingError: Record<string, unknown>;
}
