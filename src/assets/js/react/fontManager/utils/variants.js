/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';

const WEIGHTS = {
	100: __('Thin', 'gravity-pdf'),
	200: __('Extra Light', 'gravity-pdf'),
	300: __('Light', 'gravity-pdf'),
	400: __('Regular', 'gravity-pdf'),
	500: __('Medium', 'gravity-pdf'),
	600: __('Semi Bold', 'gravity-pdf'),
	700: __('Bold', 'gravity-pdf'),
	800: __('Extra Bold', 'gravity-pdf'),
	900: __('Black', 'gravity-pdf'),
};

/**
 * Read one of a catalogue row's `styles` ids
 *
 * The ids are the publisher's (`regular`, `700italic`, `300`), and the label, weight and upright/italic
 * grouping every role select shows are derived from them here rather than fetched — §4.6 is explicit that this
 * costs no extra request.
 *
 * @param {string} id
 *
 * @return {Object} `{ id, weight, italic, label, group }`
 *
 * @since 7.0
 */
export function parseStyle(id) {
	const italic = id.endsWith('italic');
	const stem = italic ? id.slice(0, -'italic'.length) : id;
	const weight = stem === '' || stem === 'regular' ? 400 : parseInt(stem, 10);
	const name = WEIGHTS[weight] ?? String(weight);

	return {
		id,
		weight,
		italic,
		label: italic
			? /* translators: %s: a font weight, e.g. "Bold" */
				sprintf(__('%s Italic', 'gravity-pdf'), name)
			: name,
		group: italic
			? __('Italic', 'gravity-pdf')
			: __('Upright', 'gravity-pdf'),
	};
}

/**
 * A row's styles, parsed and ordered the way a select should list them
 *
 * @param {?string} styles The CSV the catalogue row carries
 *
 * @return {Array<Object>} The parsed styles
 *
 * @since 7.0
 */
export function parseStyles(styles) {
	if (!styles) {
		return [];
	}

	return styles
		.split(',')
		.filter(Boolean)
		.map(parseStyle)
		.sort((a, b) => a.italic - b.italic || a.weight - b.weight);
}

/**
 * Which style each role is filled by, read off the installed file rows
 *
 * @param {Object} files Role → file row
 *
 * @return {Object} Role → style id
 *
 * @since 7.0
 */
export function variantsFromFiles(files = {}) {
	return Object.values(files).reduce((map, file) => {
		map[file.role] = file.variant ?? '';

		return map;
	}, {});
}
