/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useDispatch } from 'react-redux';
/* Redux actions */
import { selectTemplate } from '../../actions/templates';

/**
 * Renders the button used to trigger the current active PDF template
 * On click it triggers our Redux action.
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
 * @param {*}      root0.template
 * @param {*}      root0.buttonText
 * @since 4.1
 */
const TemplateActivateButton = ({ navigate, template, buttonText }) => {
	const dispatch = useDispatch();

	const handleSelectTemplate = (e) => {
		e.preventDefault();
		e.stopPropagation();

		navigate('/');
		dispatch(selectTemplate(template?.id));
	};

	return (
		<button
			data-test="component-templateActivateButton"
			type="button"
			onClick={handleSelectTemplate}
			className="button activate"
			aria-label={buttonText + ' ' + GFPDF.template}
		>
			{buttonText}
		</button>
	);
};

TemplateActivateButton.propTypes = {
	navigate: PropTypes.func,
	template: PropTypes.object,
	buttonText: PropTypes.string,
};

export default TemplateActivateButton;
