/* Dependencies */
import { __ } from '@wordpress/i18n';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

const FontListHeader = () => (
	<div data-test="component-FontListHeader" className="font-list-header">
		<div className="font-name">{__('Installed Fonts', 'gravity-pdf')}</div>
		<div>{__('Regular', 'gravity-pdf')}</div>
		<div>{__('Italics', 'gravity-pdf')}</div>
		<div>{__('Bold', 'gravity-pdf')}</div>
		<div>{__('Bold Italics', 'gravity-pdf')}</div>
		<div />
	</div>
);

export default FontListHeader;
