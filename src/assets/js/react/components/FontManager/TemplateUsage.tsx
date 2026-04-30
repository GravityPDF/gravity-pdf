/* Dependencies */
import { useState, useId } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { check, chevronRight, copy as copyIcon } from '@wordpress/icons';
/* Utilities */
import { templateSnippet } from '../../utilities/FontManager/templateSnippet';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	id: string;
	font_name: string;
}

const TemplateUsage = ({ id, font_name: fontName }: Props) => {
	const [open, setOpen] = useState(false);
	const [copied, setCopied] = useState(false);
	const panelId = useId();
	const buttonId = useId();

	const { snippet } = templateSnippet({ id, font_name: fontName });

	const handleCopy = async () => {
		try {
			await navigator.clipboard.writeText(snippet);
			setCopied(true);
			speak(__('Snippet copied to clipboard.', 'gravity-pdf'));
			setTimeout(() => setCopied(false), 1500);
		} catch {
			/* Clipboard API unavailable or permission denied — silently no-op */
		}
	};

	return (
		<div data-test="component-TemplateUsage" className="gfpdf-fm-template">
			<Button
				id={buttonId}
				variant="secondary"
				onClick={() => setOpen((prev) => !prev)}
				aria-expanded={open}
				aria-controls={panelId}
				icon={chevronRight}
				className={
					open
						? 'gfpdf-fm-template__disclosure is-open'
						: 'gfpdf-fm-template__disclosure'
				}
			>
				{open
					? __('Hide template usage', 'gravity-pdf')
					: __('Show template usage', 'gravity-pdf')}
			</Button>
			{open && (
				<div
					id={panelId}
					role="region"
					aria-labelledby={buttonId}
					className="gfpdf-fm-template__panel"
				>
					<p className="gfpdf-fm-template__desc">
						{__(
							"Add this snippet to a custom PDF template to apply this font to specific text. For example, place it inside a header or a signature block. To use this font throughout an entire PDF, open the form's Font setting instead.",
							'gravity-pdf'
						)}
					</p>
					<div className="gfpdf-fm-code-block">
						<div className="gfpdf-fm-code-block__header">
							<span className="gfpdf-fm-code-block__lang">
								HTML &middot; CSS
							</span>
							<Button
								variant="secondary"
								size="small"
								icon={copied ? check : copyIcon}
								onClick={handleCopy}
								aria-label={__('Copy snippet', 'gravity-pdf')}
							>
								{copied
									? __('Copied', 'gravity-pdf')
									: __('Copy snippet', 'gravity-pdf')}
							</Button>
						</div>
						<pre className="gfpdf-fm-code-block__body">
							<code>{snippet}</code>
						</pre>
					</div>
				</div>
			)}
		</div>
	);
};

export default TemplateUsage;
