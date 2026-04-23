/* Dependencies */
import { useRef, useEffect } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { NavigateFunction } from 'react-router';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import { TEMPLATE_STORE_NAME, templateStore } from '../../store/templateStore';
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
	callbackFunction?: (e: MouseEvent<HTMLButtonElement>) => void;
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
	const {
		addTemplate,
		deleteTemplate,
		templateProcessing,
		clearTemplateProcessing,
	} = useDispatch(TEMPLATE_STORE_NAME);
	const getTemplateProcessing = useSelect(
		(select) => select(templateStore).getTemplateProcessing(),
		[]
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
			addTemplate({
				...template,
				error: templateDeleteErrorText,
			} as TemplateItem);
			navigate('/template');
			clearTemplateProcessing();
		}
	}, [
		getTemplateProcessing,
		navigate,
		addTemplate,
		clearTemplateProcessing,
		template,
		templateDeleteErrorText,
	]);

	const handleDeleteTemplate = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		if (window.confirm(templateConfirmDeleteText)) {
			if (template?.id) {
				templateProcessing(template.id);
				deleteTemplate(template.id);
			}
		}
	};

	const handleClick = callbackFunction || handleDeleteTemplate;

	return (
		<button
			data-test="component-templateDeleteButton"
			type="button"
			onClick={handleClick}
			className="button button-secondary delete-theme ed_button"
			aria-label={buttonText + ' ' + __('Template', 'gravity-pdf')}
		>
			{buttonText}
		</button>
	);
};

export default TemplateDeleteButton;
