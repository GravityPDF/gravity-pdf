/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';

/**
 * Route words a font key may never equal (§4.7)
 *
 * @since 7.0
 */
export const RESERVED = [
	'sources',
	'status',
	'settings',
	'updates',
	'browse',
	'sync',
];

/**
 * The mPDF key a label derives to — what a template will write in `font-family`
 *
 * The server derives it again and suffixes a conflict rather than refusing, so this is a preview of the
 * answer, not the answer. It is shown live under the name field so nobody learns their key after the fact.
 *
 * @param {string} label
 *
 * @return {string} The key
 *
 * @since 7.0
 */
export function deriveKey(label) {
	const key = (label ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');

	return RESERVED.includes(key) ? `${key}font` : key;
}

/**
 * Whether a font name is one the server will take
 *
 * @param {string}        label
 * @param {Array<string>} taken Keys already in use, the row being edited excluded
 *
 * @return {string} The error, or an empty string
 *
 * @since 7.0
 */
export function validateName(label, taken = []) {
	const trimmed = (label ?? '').trim();

	if (!trimmed) {
		return __('Font name is required', 'gravity-pdf');
	}

	if (!/^[A-Za-z0-9 ]+$/.test(trimmed)) {
		return __('Use letters, numbers, and spaces only', 'gravity-pdf');
	}

	if (taken.includes(deriveKey(trimmed))) {
		return __('A font with this name already exists', 'gravity-pdf');
	}

	return '';
}
