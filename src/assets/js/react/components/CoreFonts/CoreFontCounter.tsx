/* Dependencies */
import React from 'react';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

interface Props {
	queue?: number;
	text?: string;
}

const CoreFontCounter = ({ queue, text }: Props) => (
	<span
		data-test="component-coreFont-counter"
		className="gfpdf-core-font-counter"
	>
		{text} {queue}
	</span>
);

export default CoreFontCounter;
