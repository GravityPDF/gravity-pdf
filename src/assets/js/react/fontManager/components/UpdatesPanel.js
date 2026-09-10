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
import { fileSize } from '../utils/format';
import DetailHeader from './DetailHeader';
import UpdateRow from './UpdateRow';

/**
 * Every pending update, across every source
 *
 * @param {Object}   props
 * @param {Function} props.onBack
 *
 * @return {JSX.Element} The panel
 *
 * @since 7.0
 */
export default function UpdatesPanel({ onBack }) {
	const { updates, statuses, labels, busy } = useSelect((select) => {
		const store = select(STORE_NAME);

		return {
			updates: store.getUpdates(),
			statuses: store.getStatuses(),
			labels: store.getSourceLabels(),
			busy: store.isBusy('updates'),
		};
	}, []);

	const { updateAllEntries, installEntry } = useDispatch(STORE_NAME);

	const total = updates.reduce(
		(bytes, update) => bytes + (update.size ?? 0),
		0
	);

	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<DetailHeader
					title={__('Updates', 'gravity-pdf')}
					onBack={onBack}
				/>

				{updates.length === 0 ? (
					<p className="fm-empty-text">
						{__('Everything is up to date.', 'gravity-pdf')}
					</p>
				) : (
					<>
						<div className="gfpdf-fm-update-all">
							<Button
								variant="primary"
								isBusy={busy}
								disabled={busy}
								onClick={() => updateAllEntries()}
							>
								{sprintf(
									/* translators: %s: how much will be downloaded, e.g. "12.4 MB" */
									__('Update all · %s', 'gravity-pdf'),
									fileSize(total)
								)}
							</Button>
						</div>

						<div className="gfpdf-fm-updates">
							{updates.map((update) => (
								<UpdateRow
									key={update.id}
									update={update}
									status={statuses[update.id]}
									source={
										labels[update.source] ?? update.source
									}
									onUpdate={() =>
										installEntry(
											update.source,
											update.entry
										)
									}
								/>
							))}
						</div>
					</>
				)}
			</div>
		</section>
	);
}
