/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import {
	Button,
	Notice,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { ROLES, STORE_NAME } from '../constants';
import { deriveKey, validateName } from '../utils/fontKey';
import ConfirmDialog from './ConfirmDialog';
import DetailHeader from './DetailHeader';
import FontVariantRow from './FontVariantRow';
import PreviewSection from './PreviewSection';
import TemplateUse from './TemplateUse';

/**
 * Add or edit one uploaded font
 *
 * The mockup's screen, with three things it did not have: the derived key under the name field (a template
 * writes that, not the name), the per-site toggle multisite needs, and a Save that knows which of its changes
 * are destructive. Everything else — the four rows, the drag targets, the dirty tracking — is as designed.
 *
 * @param {Object}   props
 * @param {?Object}  props.row         The row being edited, or null when uploading
 * @param {Function} props.onBack
 * @param {Function} props.onDone      Where to go once the row is saved or deleted
 * @param {boolean}  props.hasSelect   Whether an associated font select is on the page
 * @param {Function} props.onSetActive
 *
 * @return {JSX.Element} The panel
 *
 * @since 7.0
 */
export default function CustomFontDetail({
	row,
	onBack,
	onDone,
	hasSelect,
	onSetActive,
}) {
	const [name, setName] = useState(row?.label ?? '');
	const [files, setFiles] = useState(() => filesOf(row));
	const [confirm, setConfirm] = useState(null);

	const { activeFont, taken, busy, error, canDeleteFiles } = useSelect(
		(select) => {
			const store = select(STORE_NAME);

			return {
				activeFont: store.getActiveFont(),
				taken: store.getTakenKeys(row?.id ?? ''),
				busy: store.isBusy(row?.id ?? 'new'),
				error: store.getError(row?.id ?? 'new'),
				canDeleteFiles: store.canDeleteFiles(),
			};
		},
		[row?.id]
	);

	const { saveFont, deleteFont } = useDispatch(STORE_NAME);

	useEffect(() => {
		setName(row?.label ?? '');
		setFiles(filesOf(row));
		setConfirm(null);
	}, [row]);

	const nameError = name === '' && !row ? '' : validateName(name, taken);
	const key = row?.id ?? deriveKey(name);
	const saved = filesOf(row);

	const dirty =
		name !== (row?.label ?? '') ||
		JSON.stringify(files) !== JSON.stringify(saved);

	/* A face being replaced or removed is what earns the second confirm; adding one is not destructive */
	const destructive = ROLES.some(
		(role) => saved[role.id] && saved[role.id] !== files[role.id]
	);

	const reset = () => {
		setName(row?.label ?? '');
		setFiles(saved);
	};

	const save = () => {
		if (validateName(name, taken)) {
			return;
		}

		if (destructive) {
			setConfirm('replace');

			return;
		}

		commit();
	};

	const commit = async () => {
		setConfirm(null);

		const written = await saveFont(row?.id ?? null, { label: name, files });

		if (written) {
			onDone(written.id);
		}
	};

	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<DetailHeader
					title={
						row
							? __('Edit font', 'gravity-pdf')
							: __('Add font', 'gravity-pdf')
					}
					onBack={onBack}
					active={!!row && activeFont === row.id}
					canSetActive={
						hasSelect && !!row && !!row.files?.R && !dirty
					}
					onSetActive={() => onSetActive(row.id)}
				/>

				{error && (
					<Notice status="error" isDismissible={false}>
						{error}
					</Notice>
				)}

				<div className="section">
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={__('Font name', 'gravity-pdf')}
						value={name}
						onChange={setName}
						maxLength={50}
						placeholder={__(
							'e.g. Helvetica Neue, Roboto Slab, Source Sans 3',
							'gravity-pdf'
						)}
						className={nameError ? 'has-error' : ''}
						help={
							nameError ||
							__(
								'Letters, numbers, and spaces only',
								'gravity-pdf'
							)
						}
					/>

					{key && <TemplateUse fontKey={key} />}
				</div>

				<div className="section">
					<h3 className="section-title">
						{__('Font files', 'gravity-pdf')}
					</h3>
					<p className="section-desc">
						{__(
							'Add a .ttf file for each style you want to use in your PDFs. Regular is required; Italic, Bold, and Bold Italic are optional and only used when your templates need them.',
							'gravity-pdf'
						)}
					</p>

					<div className="variants">
						{ROLES.map((role) => (
							<FontVariantRow
								key={role.id}
								role={role}
								filename={files[role.id] ?? null}
								onFile={(file) =>
									setFiles((previous) => ({
										...previous,
										[role.id]: file.name,
									}))
								}
								onDelete={() =>
									setFiles((previous) => {
										const next = { ...previous };
										delete next[role.id];

										return next;
									})
								}
							/>
						))}
					</div>
				</div>

				{row && <PreviewSection fontKey={row.id} files={row.files} />}

				{row && !canDeleteFiles && (
					<div className="section">
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Show on this site', 'gravity-pdf')}
							help={__(
								'The font stays installed for the network either way.',
								'gravity-pdf'
							)}
							checked={row.enabled}
							onChange={(enabled) =>
								saveFont(row.id, { enabled })
							}
						/>
					</div>
				)}
			</div>

			<div className="fm-actions">
				{row && canDeleteFiles ? (
					<Button
						variant="secondary"
						isDestructive
						onClick={() => setConfirm('delete')}
					>
						{__('Delete font', 'gravity-pdf')}
					</Button>
				) : (
					<div />
				)}

				<div className="spacer" />

				<Button
					variant="tertiary"
					disabled={!dirty && !!row}
					onClick={() => (row ? reset() : onBack())}
				>
					{__('Cancel', 'gravity-pdf')}
				</Button>

				<Button
					variant="primary"
					isBusy={busy}
					disabled={
						busy ||
						!!nameError ||
						!name.trim() ||
						!files.R ||
						!dirty
					}
					onClick={save}
				>
					{row
						? __('Save changes', 'gravity-pdf')
						: __('Add font', 'gravity-pdf')}
				</Button>
			</div>

			{confirm === 'delete' && (
				<ConfirmDialog
					title={__('Delete this font?', 'gravity-pdf')}
					confirmLabel={__('Delete font', 'gravity-pdf')}
					destructive
					onCancel={() => setConfirm(null)}
					onConfirm={async () => {
						setConfirm(null);
						await deleteFont(row.id);
						onDone('');
					}}
				>
					<p>
						{__(
							'The files are removed from the site. Any PDF set to use this font falls back to the default font, and re-renders the next time it is generated.',
							'gravity-pdf'
						)}
					</p>
				</ConfirmDialog>
			)}

			{confirm === 'replace' && (
				<ConfirmDialog
					title={__('Save changes to font files?', 'gravity-pdf')}
					confirmLabel={__('Save changes', 'gravity-pdf')}
					onCancel={() => setConfirm(null)}
					onConfirm={commit}
				>
					<p>
						{__(
							'This applies to every PDF using this font. Existing PDFs may re-render with the updated files.',
							'gravity-pdf'
						)}
					</p>
				</ConfirmDialog>
			)}
		</section>
	);
}

function filesOf(row) {
	return Object.values(row?.files ?? {}).reduce((map, file) => {
		map[file.role] = file.path;

		return map;
	}, {});
}
