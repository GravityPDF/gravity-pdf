/* Dependencies */
import * as React from '@wordpress/element';
import { useState, createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
				{__('View template usage', 'gravity-pdf')}
			</button>

			{tooltip && (
				<div>
					{createInterpolateElement(
						__(
							'Add this snippet <link1>in a custom template</link1> to selectively set the font on blocks of text. If you want to apply the font to the entire PDF, <link2>use the Font setting</link2> when configuring the PDF on the form.',
							'gravity-pdf'
						),
						{
							/* eslint-disable jsx-a11y/anchor-has-content */
							link1: (
								<a href="https://docs.gravitypdf.com/developers/first-custom-pdf" />
							),
							link2: (
								<a href="https://docs.gravitypdf.com/users/setup-pdf#font" />
							),
							/* eslint-enable jsx-a11y/anchor-has-content */
						}
					)}
				</div>
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
