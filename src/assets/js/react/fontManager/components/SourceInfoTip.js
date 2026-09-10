/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { info } from '@wordpress/icons';

/**
 * What a source is, on hover or focus
 *
 * The description belongs to the source record, so a third-party source explains itself with no code here —
 * and it stays out of the list, where a paragraph per source would crowd out the fonts.
 *
 * @param {Object}  props
 * @param {?Object} props.source The source record
 *
 * @return {?JSX.Element} The icon, or null when there is nothing to say
 *
 * @since 7.0
 */
export default function SourceInfoTip({ source }) {
	if (!source?.description) {
		return null;
	}

	return (
		<Tooltip text={source.description}>
			<Button
				icon={info}
				size="small"
				label={__('About this source', 'gravity-pdf')}
				className="gfpdf-fm-source-info"
			/>
		</Tooltip>
	);
}
