/* Dependencies */
import * as React from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { NavigateFunction } from 'react-router-dom';

/**
 * Render the button used to option our Fancy PDF template selector
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	navigate: NavigateFunction;
}

const TemplateButton = ({ navigate }: Props) => {
	const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
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
			aria-label={__('Manage PDF Templates', 'gravity-pdf')}
		>
			{__('Manage', 'gravity-pdf')}
		</button>
	);
};

export default TemplateButton;
