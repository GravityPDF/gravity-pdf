/* Dependencies */
import { __ } from '@wordpress/i18n';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

interface Props {
	queue?: number;
}

const CoreFontCounter = ({ queue }: Props) => (
	<span
		data-test="component-coreFont-counter"
		className="gfpdf-core-font-counter"
	>
		{__('Fonts remaining:', 'gravity-pdf')} {queue}
	</span>
);

export default CoreFontCounter;
