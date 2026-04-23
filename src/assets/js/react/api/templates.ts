/* Dependencies */
import apiFetch from '@wordpress/api-fetch';
/* APIs */
import { getJsonString } from './api';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

async function ajaxFetch(formData: FormData): Promise<string> {
	const response = await apiFetch({
		url: GFPDF.ajaxUrl,
		method: 'POST',
		body: formData,
		parse: false,
	});
	if (!response.ok) {
		throw new Error(`Request failed: ${response.status}`);
	}
	return response.text();
}

export async function apiPostUpdateSelectBox(): Promise<string> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_get_template_options');
	formData.append('nonce', GFPDF.ajaxNonce);
	return ajaxFetch(formData);
}

export async function apiPostTemplateProcessing(
	templateId: string
): Promise<unknown> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_delete_template');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('id', templateId);
	const text = await ajaxFetch(formData);
	return getJsonString(text);
}

export async function apiPostTemplateUploadProcessing(
	file: File,
	filename: string
): Promise<unknown> {
	const formData = new window.FormData();
	formData.append('action', 'gfpdf_upload_template');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('template', file, filename);
	const text = await ajaxFetch(formData);
	return getJsonString(text);
}
