/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Render the button used to option our Fancy PDF template selector
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.navigate
 * @since 4.1
 */
const TemplateButton = ({ navigate }) => {
	const handleClick = (e) => {
		e.preventDefault();
		e.stopPropagation();

		navigate('template');
	};

	return (
		<button
			data-test="component-templateButton"
			type="button"
			id="fancy-template-selector"
			className="button gfpdf-button"
			onClick={handleClick}
			aria-label={GFPDF.manageTemplates}
		>
			{GFPDF.manage}
		</button>
	);
};

TemplateButton.propTypes = {
	navigate: PropTypes.func,
};

export default TemplateButton;
