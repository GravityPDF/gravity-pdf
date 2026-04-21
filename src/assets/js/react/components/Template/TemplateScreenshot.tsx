/* Dependencies */
import React from 'react';

/**
 * Display the Template Screenshot for the List Items
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	image?: string;
}

const TemplateScreenshot = ({ image }: Props) => {
	const className = image ? 'theme-screenshot' : 'theme-screenshot blank';

	return (
		<div data-test="component-templateScreenshot" className={className}>
			{image ? <img src={image} alt="" /> : null}
		</div>
	);
};

export default TemplateScreenshot;
