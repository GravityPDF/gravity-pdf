/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Display an inline counter
 *
 * @param { Object } props
 * @param { string } props.queue
 * @param { string } props.text
 *
 * @return { JSX.Element } CoreFontCounter Component
 *
 * @since 5.0
 */
const CoreFontCounter = ({ queue, text }) => (
	<span
		data-test="component-coreFont-counter"
		className="gfpdf-core-font-counter"
	>
		{text} {queue}
	</span>
);

/**
 *
 * @since 5.0
 */
CoreFontCounter.propTypes = {
	queue: PropTypes.number,
	text: PropTypes.string,
};

export default CoreFontCounter;
