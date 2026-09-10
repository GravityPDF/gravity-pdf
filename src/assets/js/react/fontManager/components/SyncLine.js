/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import { timeAgo } from '../utils/format';

/**
 * The catalogue's age, and the one Refresh button
 *
 * The only place a sync time appears — every source screen reads this same summary rather than printing its
 * own. The button is quiet until something is actually due, because a control that shouts on a healthy site
 * teaches people to ignore it.
 *
 * @return {JSX.Element} The line
 *
 * @since 7.0
 */
export default function SyncLine() {
	const summary = useSelect(
		(select) => select(STORE_NAME).getSyncSummary(),
		[]
	);
	const busy = useSelect(
		(select) => select(STORE_NAME).isBusy('sources'),
		[]
	);
	const { refreshSources } = useDispatch(STORE_NAME);

	return (
		<div className="gfpdf-fm-sync">
			<span className="gfpdf-fm-sync-text">{syncText(summary)}</span>

			<Button
				variant={summary.stale ? 'primary' : 'tertiary'}
				size="small"
				isBusy={busy}
				disabled={busy}
				onClick={() => refreshSources()}
			>
				{__('Refresh', 'gravity-pdf')}
			</Button>
		</div>
	);
}

/**
 * What the line says, given the summary
 *
 * @param {Object} summary `getSyncSummary()`
 *
 * @return {string} The sentence
 *
 * @since 7.0
 */
export function syncText(summary) {
	if (summary.never || !summary.synced) {
		return __('Catalogue not downloaded yet', 'gravity-pdf');
	}

	const updated = sprintf(
		/* translators: %s: how long ago the catalogue was updated, e.g. "3 days ago" */
		__('Updated %s', 'gravity-pdf'),
		timeAgo(summary.synced)
	);

	return summary.failed
		? `${updated} · ${__('last refresh failed', 'gravity-pdf')}`
		: updated;
}
