/* Dependencies */
import { __ } from '@wordpress/i18n';

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
				{__('Regular', 'gravity-pdf')}{' '}
				<span className="required">
					{__('(required)', 'gravity-pdf')}
				</span>
			</span>
		)}
		{label === 'regular' && font === 'true' && __('Regular', 'gravity-pdf')}
		{label === 'italics' && __('Italics', 'gravity-pdf')}
		{label === 'bold' && __('Bold', 'gravity-pdf')}
		{label === 'bolditalics' && __('Bold Italics', 'gravity-pdf')}
	</label>
);

export default FontVariantLabel;
