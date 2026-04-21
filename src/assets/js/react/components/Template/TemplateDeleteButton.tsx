/* Dependencies */
import React, { useRef, useEffect } from 'react';
import { NavigateFunction } from 'react-router-dom';
/* Redux hooks and actions */
import { useAppSelector, useAppDispatch } from '../../store/hooks';
import {
	addTemplate as addTemplateAction,
	deleteTemplate as deleteTemplateAction,
	templateProcessing as templateProcessingAction,
	clearTemplateProcessing as clearTemplateProcessingAction,
} from '../../actions/templates';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Renders a delete button which then queries our server and
 * removes the selected PDF template
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	navigate: NavigateFunction;
	template?: TemplateItem;
	callbackFunction?: (e: React.MouseEvent<HTMLButtonElement>) => void;
	buttonText?: string;
	templateConfirmDeleteText?: string;
	templateDeleteErrorText?: string;
	ajaxUrl?: string;
	ajaxNonce?: string;
}

const TemplateDeleteButton = ({
	navigate,
	template,
	callbackFunction,
	buttonText,
	templateConfirmDeleteText,
	templateDeleteErrorText,
}: Props) => {
	const dispatch = useAppDispatch();
	const getTemplateProcessing = useAppSelector(
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
				} as TemplateItem)
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

	const deleteTemplate = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		if (window.confirm(templateConfirmDeleteText)) {
			if (template?.id) {
				dispatch(templateProcessingAction(template.id));
				dispatch(deleteTemplateAction(template.id));
			}
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

export default TemplateDeleteButton;
