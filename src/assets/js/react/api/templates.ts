/* Dependencies */
import { api, getJsonString } from './api';
/* Types */
import { ApiResponse } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

export async function apiPostUpdateSelectBox(): Promise<ApiResponse<string>> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_get_template_options');
	formData.append('nonce', GFPDF.ajaxNonce);

	const response = await api(GFPDF.ajaxUrl, {
		method: 'POST',
		body: formData,
	});

	const text = await response.text();

	return {
		body: text,
		text,
		status: response.status,
		ok: response.ok,
	};
}

export async function apiPostTemplateProcessing(
	templateId: string
): Promise<ApiResponse<unknown>> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_delete_template');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('id', templateId);

	const response = await api(GFPDF.ajaxUrl, {
		method: 'POST',
		body: formData,
	});

	const text = await response.text();
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
}

export async function apiPostTemplateUploadProcessing(
	file: File,
	filename: string
): Promise<ApiResponse<unknown>> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_upload_template');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('template', file, filename);

	const response = await api(GFPDF.ajaxUrl, {
		method: 'POST',
		body: formData,
	});

	const text = await response.text();
	const body = getJsonString(text);

	return {
		body,
		text,
		status: response.status,
		ok: response.ok,
	};
}
