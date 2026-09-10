/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button, ProgressBar } from '@wordpress/components';
import { isInstalling } from '../utils/install';

/**
 * What an install looks like while it is happening, and when it stops
 *
 * One component for the family card, the pack row, the entry page and the Updates row, because the four of
 * them read the same status object and any difference between them would be a lie about the same install.
 * A coverage entry counts files; a display entry cannot, because it has no single total.
 *
 * @param {Object}   props
 * @param {?Object}  props.status   The status object
 * @param {?number}  props.total    The entry's file count, for a coverage entry
 * @param {boolean}  props.updating Whether this is an update rather than a first install
 * @param {Function} props.onRetry
 *
 * @return {?JSX.Element} The progress line, or null when nothing is happening
 *
 * @since 7.0
 */
export default function InstallProgress({
	status,
	total = 0,
	updating = false,
	onRetry,
}) {
	if (!status) {
		return null;
	}

	if (status.stuck) {
		return (
			<span className="gfpdf-fm-progress is-stuck">
				{__('Stuck', 'gravity-pdf')}
				<Button variant="link" onClick={onRetry}>
					{__('Retry', 'gravity-pdf')}
				</Button>
			</span>
		);
	}

	if (status.phase === 'failed') {
		return (
			<span className="gfpdf-fm-progress is-failed">
				{status.error || __('The install failed', 'gravity-pdf')}
				<Button variant="link" onClick={onRetry}>
					{__('Retry', 'gravity-pdf')}
				</Button>
			</span>
		);
	}

	if (!isInstalling(status)) {
		return null;
	}

	return (
		<span className="gfpdf-fm-progress">
			{label(status, total, updating)}
			<ProgressBar
				className="gfpdf-fm-progress-bar"
				value={
					total > 0
						? Math.round(((status.files_done ?? 0) / total) * 100)
						: undefined
				}
			/>
		</span>
	);
}

function label(status, total, updating) {
	if (total > 0) {
		const done = status.files_done ?? 0;

		return updating
			? sprintf(
					/* translators: 1: files done so far, 2: how many the entry holds */
					__('Updating · %1$d of %2$d', 'gravity-pdf'),
					done,
					total
				)
			: sprintf(
					/* translators: 1: files done so far, 2: how many the entry holds */
					__('Installing · %1$d of %2$d', 'gravity-pdf'),
					done,
					total
				);
	}

	return updating
		? __('Updating…', 'gravity-pdf')
		: __('Installing…', 'gravity-pdf');
}
