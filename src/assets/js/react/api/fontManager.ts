/* Dependencies */
import apiFetch from '@wordpress/api-fetch';
import { serialize } from 'object-to-formdata';
/* Types */
import { FontItem, FontFormData } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export const apiGetCustomFontList = (
	signal?: AbortSignal
): Promise<FontItem[]> =>
	apiFetch<FontItem[]>({ path: '/gfpdf/v1/fonts/', signal });

export const apiAddFont = (
	font: FontFormData,
	signal?: AbortSignal
): Promise<FontItem> => {
	const formData = serialize(font);
	return apiFetch<FontItem>({
		path: '/gfpdf/v1/fonts/',
		method: 'POST',
		body: formData,
		signal,
	});
};

export const apiEditFont = ({
	id,
	font,
	signal,
}: {
	id: string;
	font: Partial<FontFormData>;
	signal?: AbortSignal;
}): Promise<FontItem> => {
	const formData = serialize({ ...font });
	return apiFetch<FontItem>({
		path: `/gfpdf/v1/fonts/${id}`,
		method: 'POST',
		body: formData,
		signal,
	});
};

export const apiDeleteFont = (
	id: string,
	signal?: AbortSignal
): Promise<unknown> =>
	apiFetch({ path: `/gfpdf/v1/fonts/${id}`, method: 'DELETE', signal });
