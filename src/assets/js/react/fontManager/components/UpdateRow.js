/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { update as updateIcon } from '@wordpress/icons';
import { fileSize } from '../utils/format';
import { isInstalling } from '../utils/install';
import InstallProgress from './InstallProgress';

/**
 * One pending update
 *
 * The row carries `notes` and `released` because "why does this version exist" is the question an admin has to
 * answer before letting every cached PDF re-render.
 *
 * @param {Object}   props
 * @param {Object}   props.update   A `getUpdates()` row
 * @param {?Object}  props.status   Its status object
 * @param {string}   props.source   The source's label
 * @param {Function} props.onUpdate
 *
 * @return {JSX.Element} The row
 *
 * @since 7.0
 */
export default function UpdateRow({ update, status, source, onUpdate }) {
	const running = isInstalling(status);

	return (
		<div className="gfpdf-fm-update-row">
			<div className="gfpdf-fm-update-meta">
				<div className="gfpdf-fm-update-name">
					{update.entry}
					<span className="gfpdf-fm-update-source">{source}</span>
				</div>

				<div className="gfpdf-fm-update-version">
					{sprintf(
						/* translators: 1: installed version, 2: the version waiting, 3: file count, 4: size */
						__('%1$s → %2$s · %3$d files · %4$s', 'gravity-pdf'),
						update.installed_version,
						update.version,
						update.files,
						fileSize(update.size)
					)}
				</div>

				{update.notes && (
					<div className="gfpdf-fm-update-notes">
						{update.notes}
						{update.released && ` (${update.released})`}
					</div>
				)}
			</div>

			{running ? (
				<InstallProgress status={status} updating onRetry={onUpdate} />
			) : (
				<Button
					variant="secondary"
					size="small"
					icon={updateIcon}
					onClick={onUpdate}
				>
					{__('Update', 'gravity-pdf')}
				</Button>
			)}
		</div>
	);
}
