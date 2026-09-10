/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * A byte count, at the scale a person reads it
 *
 * @param {?number} bytes
 *
 * @return {string} e.g. "10.6 MB"
 *
 * @since 7.0
 */
export function fileSize(bytes) {
	if (!bytes) {
		return '';
	}

	if (bytes < 1024) {
		/* translators: %d: a number of bytes */
		return sprintf(__('%d B', 'gravity-pdf'), bytes);
	}

	const kb = bytes / 1024;

	if (kb < 1024) {
		/* translators: %s: a number of kilobytes */
		return sprintf(__('%s KB', 'gravity-pdf'), Math.round(kb));
	}

	/* translators: %s: a number of megabytes */
	return sprintf(__('%s MB', 'gravity-pdf'), (kb / 1024).toFixed(1));
}

const MINUTE = 60;
const HOUR = 3600;
const DAY = 86400;

/**
 * How long ago a timestamp was, in the one phrase the sync line shows
 *
 * @param {?string} timestamp An ISO 8601 date
 *
 * @return {string} e.g. "3 days ago"
 *
 * @since 7.0
 */
export function timeAgo(timestamp) {
	if (!timestamp) {
		return '';
	}

	const seconds = Math.max(
		0,
		Math.round((Date.now() - Date.parse(timestamp)) / 1000)
	);

	if (seconds < MINUTE) {
		return __('just now', 'gravity-pdf');
	}

	if (seconds < HOUR) {
		const minutes = Math.round(seconds / MINUTE);

		return sprintf(
			/* translators: %d: a number of minutes */
			_n('%d minute ago', '%d minutes ago', minutes, 'gravity-pdf'),
			minutes
		);
	}

	if (seconds < DAY) {
		const hours = Math.round(seconds / HOUR);

		return sprintf(
			/* translators: %d: a number of hours */
			_n('%d hour ago', '%d hours ago', hours, 'gravity-pdf'),
			hours
		);
	}

	const days = Math.round(seconds / DAY);

	return sprintf(
		/* translators: %d: a number of days */
		_n('%d day ago', '%d days ago', days, 'gravity-pdf'),
		days
	);
}

/**
 * The filter vocabulary the routes will translate, until they do
 *
 * @since 7.0
 */
const KNOWN = {
	latin: __('Latin', 'gravity-pdf'),
	'latin-ext': __('Latin Extended', 'gravity-pdf'),
	cyrillic: __('Cyrillic', 'gravity-pdf'),
	greek: __('Greek', 'gravity-pdf'),
	arabic: __('Arabic', 'gravity-pdf'),
	hebrew: __('Hebrew', 'gravity-pdf'),
	devanagari: __('Devanagari', 'gravity-pdf'),
	vietnamese: __('Vietnamese', 'gravity-pdf'),
	'sans-serif': __('Sans Serif', 'gravity-pdf'),
	serif: __('Serif', 'gravity-pdf'),
	monospace: __('Monospace', 'gravity-pdf'),
	display: __('Display', 'gravity-pdf'),
	handwriting: __('Handwriting', 'gravity-pdf'),
};

/**
 * A filter id as a person reads it, title-cased when the vocabulary has no entry
 *
 * @param {string} id
 *
 * @return {string} The label
 *
 * @since 7.0
 */
export function filterLabel(id) {
	return (
		KNOWN[id] ??
		id
			.split('-')
			.map((part) => part.charAt(0).toUpperCase() + part.slice(1))
			.join(' ')
	);
}
