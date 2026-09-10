/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { cog, close } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import SyncLine from './SyncLine';

/**
 * The bar across the top of every screen
 *
 * @param {Object}   props
 * @param {Function} props.navigate
 * @param {Function} props.onClose
 *
 * @return {JSX.Element} The header
 *
 * @since 7.0
 */
export default function FontManagerHeader({ navigate, onClose }) {
	const count = useSelect(
		(select) => select(STORE_NAME).getAllRows().length,
		[]
	);

	return (
		<div className="fm-header">
			<div className="fm-title">{__('Font manager', 'gravity-pdf')}</div>
			<div className="fm-count">
				{sprintf(
					/* translators: %d: how many fonts are installed */
					_n(
						'%d font installed',
						'%d fonts installed',
						count,
						'gravity-pdf'
					),
					count
				)}
			</div>

			<div className="fm-header-spacer" />

			<SyncLine />

			<Button
				icon={cog}
				label={__('Language settings', 'gravity-pdf')}
				onClick={() => navigate('/fontmanager/settings')}
			/>
			<Button
				icon={close}
				label={__('Close dialog', 'gravity-pdf')}
				onClick={onClose}
			/>
		</div>
	);
}
