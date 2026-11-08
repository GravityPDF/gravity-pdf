/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

const PATHS = {
	check: ['M5 12.5l4.5 4.5L19 7.5'],
	'chevron-down': ['M6 9l6 6 6-6'],
	'chevron-right': ['M9 6l6 6-6 6'],
	external: [
		'M15 3h6v6',
		'M10 14 21 3',
		'M18 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5',
	],
	lock: [
		'M7 11V7a5 5 0 0 1 10 0v4',
		'M6 11h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1Z',
	],
	search: ['M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z', 'M21 21l-4.35-4.35'],
	trash: [
		'M3 6h18',
		'M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6',
		'M10 11v6',
		'M14 11v6',
		'M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2',
	],
};

/**
 * A decorative outline icon in the current text colour, sized by the stylesheet
 *
 * @param { Object } props
 * @param { string } props.name
 *
 * @return { JSX.Element } Icon component
 *
 * @since 6.19.0
 */
const Icon = ({ name }) => (
	<svg
		className={'font-icon font-icon-' + name}
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.6"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		focusable="false"
	>
		{PATHS[name].map((path) => (
			<path key={path} d={path} />
		))}
	</svg>
);

/**
 * PropTypes
 *
 * @since 6.19.0
 */
Icon.propTypes = {
	name: PropTypes.oneOf(Object.keys(PATHS)).isRequired,
};

export default Icon;
