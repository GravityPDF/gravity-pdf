/* Dependencies */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	onOpen: () => void;
}

const AdvancedButton = ({ onOpen }: Props) => (
	<Button
		data-test="component-AdvancedButton"
		variant="secondary"
		onClick={onOpen}
		__next40pxDefaultSize={true}
	>
		{__('Manage', 'gravity-pdf')}
	</Button>
);

export default AdvancedButton;
