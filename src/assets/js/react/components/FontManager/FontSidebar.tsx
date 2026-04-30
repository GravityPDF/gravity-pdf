/* Dependencies */
import { Button } from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/* Components */
import SearchBox from './SearchBox';
import FontList from './FontList';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	onSelect: (id: string) => void;
	onAddFont: () => void;
}

const FontSidebar = ({ onSelect, onAddFont }: Props) => (
	<aside data-test="component-FontSidebar" className="gfpdf-fm-sidebar">
		<div className="gfpdf-fm-sidebar__top">
			<SearchBox />
			<Button
				data-test="component-AddFontButton"
				variant="secondary"
				icon={plus}
				onClick={onAddFont}
				className="gfpdf-fm-add-button"
				__next40pxDefaultSize
			>
				{__('Add new font', 'gravity-pdf')}
			</Button>
		</div>
		<FontList onSelect={onSelect} />
	</aside>
);

export default FontSidebar;
