/* Dependencies */
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

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
		/* Parent Gravity Forms row has a click listener we don't want to fire */
		e.stopPropagation();
		onOpen();
	};

	return (
		<Button
			data-test="component-templateButton"
			variant="secondary"
			onClick={handleClick}
			aria-label={__('Manage PDF Templates', 'gravity-pdf')}
			__next40pxDefaultSize={true}
		>
			{__('Manage', 'gravity-pdf')}
		</Button>
	);
};

export default TemplateButton;
