/* Dependencies */
import React from 'react';

/**
 * Display the Template Screenshot for the individual templates (uses different markup - out of our control)
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	image?: string;
}

const TemplateScreenshots = ({ image }: Props) => {
	const className = image ? 'screenshot' : 'screenshot blank';

	return (
		<div
			data-test="component-templateScreenshots"
			className="theme-screenshots"
		>
			<div className={className}>
				{image ? <img src={image} alt="" /> : null}
			</div>
		</div>
	);
};

export default TemplateScreenshots;
