/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
/* Utilities */
import { getStyleLabel } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display information for drop box font variant label
 *
 * @param { Object } props
 * @param { string } props.label
 *
 * @since 6.0
 */
const FontVariantLabel = ({ label }) => (
	<div data-test="component-FontVariantLabel" className="font-variant-label">
		{getStyleLabel(label)}
		{label === 'regular' && (
			<span className="required">{GFPDF.fontManagerRequiredLabel}</span>
		)}
	</div>
);

/**
 * PropTypes
 *
 * @since 6.0
 */
FontVariantLabel.propTypes = {
	label: PropTypes.string.isRequired,
};

export default FontVariantLabel;
