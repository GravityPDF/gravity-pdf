/* Dependencies */
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Render the button used to open our Fancy PDF template selector
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	onOpen: () => void;
}

const TemplateButton = ({ onOpen }: Props) => {
	const handleClick = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		onOpen();
	};

	return (
		<button
			data-test="component-templateButton"
			type="button"
			id="fancy-template-selector"
			className="button gfpdf-button"
			onClick={handleClick}
			aria-label={__('Manage PDF Templates', 'gravity-pdf')}
		>
			{__('Manage', 'gravity-pdf')}
		</button>
	);
};

export default TemplateButton;
