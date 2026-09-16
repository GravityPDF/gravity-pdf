/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { Spinner } from '@wordpress/components';

/**
 * The detail pane while its read is in flight
 *
 * @return {JSX.Element} The pane
 *
 * @since 7.0
 */
export default function DetailLoading() {
	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<Spinner />
			</div>
		</section>
	);
}
