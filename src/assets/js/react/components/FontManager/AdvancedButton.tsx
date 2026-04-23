/* Dependencies */
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	onOpen: () => void;
}

const AdvancedButton = ({ onOpen }: Props) => {
	const handleClick = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		onOpen();
	};

	return (
		<button
			data-test="component-AdvancedButton"
			type="button"
			className="button gfpdf-button"
			onClick={handleClick}
		>
			{__('Manage', 'gravity-pdf')}
		</button>
	);
};

export default AdvancedButton;
