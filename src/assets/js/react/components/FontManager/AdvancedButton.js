/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * AdvancedButton component
 *
 * @param {Object} root0
 * @param {*}      root0.navigate
 * @since 6.0
 */
const AdvancedButton = ({ navigate }) => {
	const handleClick = (e) => {
		e.preventDefault();
		navigate('/fontmanager/');
	};

	return (
		<button
			data-test="component-AdvancedButton"
			type="button"
			className="button gfpdf-button"
			onClick={handleClick}
		>
			{GFPDF.manage}
		</button>
	);
};

AdvancedButton.propTypes = {
	navigate: PropTypes.func,
};

export default AdvancedButton;
