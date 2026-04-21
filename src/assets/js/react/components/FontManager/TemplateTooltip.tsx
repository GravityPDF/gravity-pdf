/* Dependencies */
import React, { useState } from 'react';
import { sprintf } from 'sprintf-js';
/* Utilities */
import { adjustFontListHeight } from '../../utilities/FontManager/adjustFontListHeight';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	id?: string;
}

const TemplateTooltip = ({ id }: Props) => {
	const [tooltip, setTooltip] = useState(false);

	const handleDisplayInfo = () => {
		setTooltip((prev) => !prev);
		setTimeout(() => adjustFontListHeight(), 100);
	};

	const handleContentHighlight = (
		e: React.SyntheticEvent<HTMLTextAreaElement>
	) => {
		const target = e.currentTarget;
		target.focus();
		target.select();
		document.execCommand('copy');
	};

	const textareaValue = `<style>
.font-${id} {
  font-family: ${id}, sans-serif;
}
</style>

<div class="font-${id}">Text</div>`;

	return (
		<div
			data-test="component-TemplateTooltip"
			className="msg template-usage-link"
		>
			{tooltip ? (
				<span className="dashicons dashicons-arrow-down-alt2" />
			) : (
				<span className="dashicons dashicons-arrow-right-alt2" />
			)}
			<button
				type="button"
				onClick={handleDisplayInfo}
				className="template-usage-link__button"
			>
				{GFPDF.fontManagerTemplateTooltipLabel}
			</button>

			{tooltip && (
				<div
					dangerouslySetInnerHTML={{
						// eslint can't detect %s found on fontManagerTemplateTooltipDesc
						// eslint-disable-next-line @wordpress/valid-sprintf
						__html: sprintf(
							GFPDF.fontManagerTemplateTooltipDesc,
							'<a href="https://docs.gravitypdf.com/developers/first-custom-pdf">',
							'<a href="https://docs.gravitypdf.com/users/setup-pdf#font">',
							'</a>'
						),
					}}
				/>
			)}

			{tooltip && (
				<textarea
					id="template_usage_info_box"
					onClick={handleContentHighlight}
					onChange={handleContentHighlight}
					value={textareaValue}
				/>
			)}
		</div>
	);
};

export default TemplateTooltip;
