/* Dependencies */
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
/* Store */
import { useDispatch } from '@wordpress/data';
import { TEMPLATE_STORE_NAME } from '../../store/templateStore';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Renders the button used to trigger the current active PDF template
 * On click it triggers our Redux action.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	onClose: () => void;
	template?: TemplateItem;
	buttonText?: string;
}

const TemplateActivateButton = ({ onClose, template, buttonText }: Props) => {
	const { selectTemplate } = useDispatch(TEMPLATE_STORE_NAME);

	const handleSelectTemplate = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		onClose();
		if (template?.id) {
			selectTemplate(template.id);
		}
	};

	return (
		<button
			data-test="component-templateActivateButton"
			type="button"
			onClick={handleSelectTemplate}
			className="button activate"
			aria-label={buttonText + ' ' + __('Template', 'gravity-pdf')}
		>
			{buttonText}
		</button>
	);
};

export default TemplateActivateButton;
