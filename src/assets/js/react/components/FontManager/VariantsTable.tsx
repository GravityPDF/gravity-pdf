/* Dependencies */
import { __ } from '@wordpress/i18n';
/* Components */
import VariantRow, { VariantDef, VariantKey } from './VariantRow';
/* Types */
import { FontVariantStyles } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

export const VARIANT_ORDER: VariantDef[] = [
	{ key: 'regular', label: __('Regular', 'gravity-pdf'), required: true },
	{ key: 'italics', label: __('Italic', 'gravity-pdf'), required: false },
	{ key: 'bold', label: __('Bold', 'gravity-pdf'), required: false },
	{
		key: 'bolditalics',
		label: __('Bold Italic', 'gravity-pdf'),
		required: false,
	},
];

interface Props {
	fontStyles: FontVariantStyles;
	onUpload: (key: VariantKey, file: File) => void;
	onDelete: (key: VariantKey) => void;
	onRejected: (message: string) => void;
}

const VariantsTable = ({
	fontStyles,
	onUpload,
	onDelete,
	onRejected,
}: Props) => (
	<div data-test="component-VariantsTable" className="gfpdf-fm-variants">
		{VARIANT_ORDER.map((variant) => (
			<VariantRow
				key={variant.key}
				variantDef={variant}
				value={fontStyles[variant.key]}
				onUpload={(file) => onUpload(variant.key, file)}
				onDelete={() => onDelete(variant.key)}
				onRejected={onRejected}
			/>
		))}
	</div>
);

export default VariantsTable;
