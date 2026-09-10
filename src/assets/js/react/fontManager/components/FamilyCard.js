/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { update as updateIcon } from '@wordpress/icons';
import { fileSize, filterLabel } from '../utils/format';
import { isInstalling } from '../utils/install';
import FontSwatch from './FontSwatch';
import InstallProgress from './InstallProgress';
import ScriptChips from './ScriptChips';

/**
 * One catalogue entry in the browser
 *
 * Install is one click at the entry's defaults — the key is the entry id and the roles are the family's
 * obvious ones — and everything else is a trip to the entry page. There is no install count on the card,
 * because every install is already its own row in the sidebar.
 *
 * @param {Object}   props
 * @param {Object}   props.entry     The catalogue row
 * @param {?Object}  props.status    Its status object
 * @param {Function} props.onOpen
 * @param {Function} props.onInstall Install, update and retry are the same post
 *
 * @return {JSX.Element} The card
 *
 * @since 7.0
 */
export default function FamilyCard({ entry, status, onOpen, onInstall }) {
	const installed = !!status?.installed;
	const updatable = !!status?.update;
	const running = isInstalling(status);

	return (
		<div className="gfpdf-fm-card">
			<button
				type="button"
				className="gfpdf-fm-card-open"
				onClick={onOpen}
			>
				<FontSwatch
					id={`${entry.source}-${entry.entry}`}
					preview={entry.preview_url}
					scripts={entry.scripts}
				/>

				<span className="gfpdf-fm-card-meta">
					<span className="gfpdf-fm-card-name">{entry.label}</span>
					<span className="gfpdf-fm-card-sub">
						{entry.category && filterLabel(entry.category)}
						{entry.category && ' · '}
						{fileSize(entry.size)}
					</span>
					{entry.coverage ? (
						<ScriptChips scripts={entry.scripts} limit={3} />
					) : null}
				</span>
			</button>

			<div className="gfpdf-fm-card-actions">
				{running ? (
					<InstallProgress
						status={status}
						total={entry.coverage ? entry.files : 0}
						updating={installed}
						onRetry={onInstall}
					/>
				) : (
					<>
						{updatable && (
							<Button
								variant="secondary"
								size="small"
								icon={updateIcon}
								onClick={onInstall}
							>
								{__('Update', 'gravity-pdf')}
							</Button>
						)}

						{installed && !updatable && (
							<span className="gfpdf-fm-card-installed">
								{__('Installed', 'gravity-pdf')}
							</span>
						)}

						{!installed && (
							<Button
								variant="primary"
								size="small"
								onClick={onInstall}
							>
								{__('Install', 'gravity-pdf')}
							</Button>
						)}

						{installed && !entry.coverage && entry.styles && (
							<Button
								variant="secondary"
								size="small"
								onClick={onOpen}
							>
								{__('Install another style', 'gravity-pdf')}
							</Button>
						)}
					</>
				)}
			</div>
		</div>
	);
}
