/* Dependencies */
import React, { useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
/* Redux actions */
import {
	addTemplate as addTemplateAction,
	deleteTemplate as deleteTemplateAction,
	templateProcessing as templateProcessingAction,
	clearTemplateProcessing as clearTemplateProcessingAction,
} from '../../actions/templates';

/**
 * Renders a delete button which then queries our server and
 * removes the selected PDF template
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
 * @param {*}      root0.callbackFunction
 * @param {*}      root0.buttonText
 * @param {*}      root0.templateConfirmDeleteText
 * @param {*}      root0.templateDeleteErrorText
 * @since 4.1
 */
const TemplateDeleteButton = ({
	navigate,
	template,
	callbackFunction,
	buttonText,
	templateConfirmDeleteText,
	templateDeleteErrorText,
}) => {
	const dispatch = useDispatch();
	const getTemplateProcessing = useSelector(
		(s) => s.template.templateProcessing
	);

	/* Track previous value to replicate componentDidUpdate comparisons */
	const prevGetTemplateProcessingRef = useRef(getTemplateProcessing);

	/* componentDidUpdate: navigate/recover when templateProcessing changes */
	useEffect(() => {
		const prev = prevGetTemplateProcessingRef.current;
		prevGetTemplateProcessingRef.current = getTemplateProcessing;

		if (prev === getTemplateProcessing) {
			return;
		}

		if (getTemplateProcessing === 'success') {
			navigate('/template');
		}

		if (getTemplateProcessing === 'failed') {
			dispatch(
				addTemplateAction({
					...template,
					error: templateDeleteErrorText,
				})
			);
			navigate('/template');
			dispatch(clearTemplateProcessingAction());
		}
	}, [
		getTemplateProcessing,
		navigate,
		dispatch,
		template,
		templateDeleteErrorText,
	]);

	const deleteTemplate = (e) => {
		e.preventDefault();
		e.stopPropagation();

		if (window.confirm(templateConfirmDeleteText)) {
			dispatch(templateProcessingAction(template?.id));
			dispatch(deleteTemplateAction(template?.id));
		}
	};

	const handleClick = callbackFunction || deleteTemplate;

	return (
		<button
			data-test="component-templateDeleteButton"
			type="button"
			onClick={handleClick}
			className="button button-secondary delete-theme ed_button"
			aria-label={buttonText + ' ' + GFPDF.template}
		>
			{buttonText}
		</button>
	);
};

TemplateDeleteButton.propTypes = {
	template: PropTypes.object,
	callbackFunction: PropTypes.func,
	navigate: PropTypes.func,
	buttonText: PropTypes.string,
	templateConfirmDeleteText: PropTypes.string,
	templateDeleteErrorText: PropTypes.string,
};

export default TemplateDeleteButton;
