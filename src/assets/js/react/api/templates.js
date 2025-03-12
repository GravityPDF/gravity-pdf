/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

import { api } from './api';

/**
 * Do AJAX call
 *
 * @return { string } response
 *
 * @since 5.2
 */
export async function apiPostUpdateSelectBox() {
	const formData = new FormData();
	formData.append('action', 'gfpdf_get_template_options');
	formData.append('nonce', GFPDF.ajaxNonce);

	const res = await api(
		GFPDF.ajaxUrl,
		{
			method: 'POST',
			body: formData,
		},
		{
			useNativeResponse: true,
		}
	);

	return res.text();
}

/**
 * Do AJAX call
 *
 * @param { string } templateId
 *
 * @return { Object } response
 *
 * @since 5.2
 */
export async function apiPostTemplateProcessing(templateId) {
	const formData = new FormData();
	formData.append('action', 'gfpdf_delete_template');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('id', templateId);

	const res = await fetch(
		GFPDF.ajaxUrl,
		{
			method: 'POST',
			body: formData,
		},
		{
			useNativeResponse: true,
		}
	);

	return res.json();
}

/**
 * Do AJAX call
 *
 * @param { Object } file
 * @param { string } filename
 *
 * @return { Object } response
 *
 * @since 5.2
 */
export async function apiPostTemplateUploadProcessing(file, filename) {
	const formData = new FormData();
	formData.append('action', 'gfpdf_upload_template');
	formData.append('nonce', GFPDF.ajaxNonce);
	formData.append('template', file, filename);

	return await api(GFPDF.ajaxUrl, {
		method: 'POST',
		body: formData,
	});
}
