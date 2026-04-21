/* Dependencies */

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	label: string;
	font?: string;
}

const FontVariantLabel = ({ label, font }: Props) => (
	<label
		data-test="component-FontVariantLabel"
		htmlFor={'gfpdf-font-variant-' + label}
	>
		{label === 'regular' && font === 'false' && (
			<span>
				{GFPDF.fontListRegular}{' '}
				<span className="required">
					{GFPDF.fontManagerRequiredLabel}
				</span>
			</span>
		)}
		{label === 'regular' && font === 'true' && GFPDF.fontListRegular}
		{label === 'italics' && GFPDF.fontListItalics}
		{label === 'bold' && GFPDF.fontListBold}
		{label === 'bolditalics' && GFPDF.fontListBoldItalics}
	</label>
);

export default FontVariantLabel;
