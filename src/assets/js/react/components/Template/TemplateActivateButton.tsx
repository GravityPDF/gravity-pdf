/* Dependencies */
import * as React from '@wordpress/element';
import { NavigateFunction } from 'react-router-dom';
/* Redux actions */
import { useAppDispatch } from '../../store/hooks';
import { selectTemplate } from '../../actions/templates';
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
	navigate: NavigateFunction;
	template?: TemplateItem;
	buttonText?: string;
}

const TemplateActivateButton = ({ navigate, template, buttonText }: Props) => {
	const dispatch = useAppDispatch();

	const handleSelectTemplate = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		navigate('/');
		if (template?.id) {
			dispatch(selectTemplate(template.id));
		}
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

export default TemplateActivateButton;
