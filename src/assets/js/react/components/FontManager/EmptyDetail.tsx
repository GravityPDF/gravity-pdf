/* Dependencies */
import { Button } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	onAddFont: () => void;
}

const EmptyDetail = ({ onAddFont }: Props) => (
	<div data-test="component-EmptyDetail" className="gfpdf-fm-empty-detail">
		<div className="gfpdf-fm-empty-detail__glyph" aria-hidden="true">
			Aa
		</div>
		<p className="gfpdf-fm-empty-detail__text">
			{__('Select a font from the list to edit it', 'gravity-pdf')}
		</p>
		<Button
			variant="secondary"
			icon={plus}
			onClick={onAddFont}
			__next40pxDefaultSize
		>
			{__('Add new font', 'gravity-pdf')}
		</Button>
	</div>
);

export default EmptyDetail;
