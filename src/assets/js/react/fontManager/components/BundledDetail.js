/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import { fileSize } from '../utils/format';
import { paths } from '../utils/paths';
import DetailHeader from './DetailHeader';
import PreviewSection from './PreviewSection';

/**
 * The face that ships with the plugin
 *
 * Read-only, and no footer: there is nothing to save and nothing to delete. Its one piece of news is the
 * warning about an `always` pack the site does not have — the emoji pack, in 7.0 — because Arimo's detail page
 * is where an admin ends up when they wonder why an emoji rendered as a box.
 *
 * @param {Object}   props
 * @param {Object}   props.row
 * @param {Function} props.onBack
 * @param {Function} props.navigate
 * @param {boolean}  props.hasSelect
 * @param {Function} props.onSetActive
 *
 * @return {JSX.Element} The panel
 *
 * @since 7.0
 */
export default function BundledDetail({
	row,
	onBack,
	navigate,
	hasSelect,
	onSetActive,
}) {
	const { missing, activeFont } = useSelect((select) => {
		const store = select(STORE_NAME);

		return {
			missing: store.getMissingEntries(),
			activeFont: store.getActiveFont(),
		};
	}, []);

	const { installEntry } = useDispatch(STORE_NAME);

	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<DetailHeader
					title={row.label}
					eyebrow={`${__(
						'Bundled with Gravity PDF',
						'gravity-pdf'
					)} · ${row.id}`}
					onBack={onBack}
					active={activeFont === row.id}
					canSetActive={hasSelect}
					onSetActive={() => onSetActive(row.id)}
				/>

				{missing.map((entry) => (
					<Notice
						key={`${entry.source}/${entry.entry}`}
						status="warning"
						isDismissible={false}
					>
						{sprintf(
							/* translators: 1: the name of a pack, 2: how big it is */
							__(
								'%1$s is not installed, so those characters render as boxes. It is %2$s.',
								'gravity-pdf'
							),
							entry.label,
							fileSize(entry.size)
						)}
						<Button
							variant="link"
							onClick={() => {
								installEntry(entry.source, entry.entry);
								navigate(
									paths.entry(entry.source, entry.entry)
								);
							}}
						>
							{__('Install', 'gravity-pdf')}
						</Button>
					</Notice>
				))}

				<div className="section">
					<p className="section-desc">{row.description}</p>
				</div>

				<PreviewSection fontKey={row.id} files={row.files} />
			</div>
		</section>
	);
}
