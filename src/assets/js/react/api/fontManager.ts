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

export const apiGetCustomFontList = (): Promise<FontItem[]> =>
	apiFetch<FontItem[]>({ path: '/gfpdf/v1/fonts/' });

export const apiAddFont = (font: FontFormData): Promise<FontItem> => {
	const formData = serialize(font);
	return apiFetch<FontItem>({
		path: '/gfpdf/v1/fonts/',
		method: 'POST',
		body: formData,
	});
};

export const apiEditFont = ({
	id,
	font,
}: {
	id: string;
	font: Partial<FontFormData>;
}): Promise<FontItem> => {
	const formData = serialize({ ...font });
	return apiFetch<FontItem>({
		path: `/gfpdf/v1/fonts/${id}`,
		method: 'POST',
		body: formData,
	});
};

export const apiDeleteFont = (id: string): Promise<unknown> =>
	apiFetch({ path: `/gfpdf/v1/fonts/${id}`, method: 'DELETE' });
