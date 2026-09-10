/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import { fileSize } from '../utils/format';
import { deriveKey, validateName } from '../utils/fontKey';
import { parseStyles, variantsFromFiles } from '../utils/variants';
import { isInstalling } from '../utils/install';
import ConfirmDialog from './ConfirmDialog';
import DetailHeader from './DetailHeader';
import DetailLoading from './DetailLoading';
import EmptyState from './EmptyState';
import EntryFontCard from './EntryFontCard';
import InstallProgress from './InstallProgress';
import PreviewSection from './PreviewSection';
import ScriptChips from './ScriptChips';
import TemplateUse from './TemplateUse';
import VariantRoles from './VariantRoles';

/**
 * One catalogue entry, in either of the two ways it is looked at
 *
 * **Row mode** is one install of the entry, reached from the sidebar: its name, its roles and a Remove that
 * takes that row alone. **Install mode** is the entry itself, reached from a card: the same fields with a name
 * to fill in and an Install at the bottom. A display entry knows nothing about its sibling installs either way
 * — that is what makes a second install of Lato an ordinary use of this screen rather than a special case.
 *
 * A coverage entry has neither mode: its names are its fonts' keys, so it shows what is inside instead.
 *
 * @param {Object}   props
 * @param {string}   props.source
 * @param {string}   props.entry
 * @param {?Object}  props.row         The install being looked at, in row mode
 * @param {Function} props.onBack
 * @param {Function} props.navigate
 * @param {boolean}  props.hasSelect
 * @param {Function} props.onSetActive
 *
 * @return {JSX.Element} The panel
 *
 * @since 7.0
 */
export default function EntryDetail({
	source,
	entry,
	row = null,
	onBack,
	navigate,
	hasSelect,
	onSetActive,
}) {
	const id = `${source}/${entry}`;

	const {
		catalog,
		record,
		status,
		rows,
		taken,
		activeFont,
		busy,
		error,
		canDeleteFiles,
	} = useSelect(
		(select) => {
			const store = select(STORE_NAME);

			return {
				catalog: store.getEntry(source, entry),
				record: store.getSource(source),
				status: store.getInstallStatus(id),
				rows: store.getEntryRows(source, entry),
				taken: store.getTakenKeys(row?.id ?? ''),
				activeFont: store.getActiveFont(),
				busy: store.isBusy(id) || store.isBusy(row?.id ?? ''),
				error: store.getError(id),
				canDeleteFiles: store.canDeleteFiles(),
			};
		},
		[source, entry, id, row?.id]
	);

	const { installEntry, removeEntry, saveFont, deleteFont } =
		useDispatch(STORE_NAME);

	const [label, setLabel] = useState('');
	const [variants, setVariants] = useState({});
	const [confirm, setConfirm] = useState(null);

	useEffect(() => {
		if (row) {
			setLabel(row.label);
			setVariants(variantsFromFiles(row.files));

			return;
		}

		/* An entry with no installs prefills its own name; a second install starts empty and asks for one */
		setLabel(rows.length === 0 ? (catalog?.label ?? '') : '');
		setVariants(defaultsFor(catalog));
	}, [row, catalog, rows.length]);

	if (catalog === null) {
		return <DetailLoading />;
	}

	if (catalog === false) {
		return <GoneDetail onBack={onBack} />;
	}

	const installed = !!status?.installed;
	const running = isInstalling(status);
	const nameError = row ? validateName(label, taken) : keyError(label, taken);
	const key = row?.id ?? deriveKey(label);
	const rolesChanged =
		JSON.stringify(variants) !==
		JSON.stringify(
			row ? variantsFromFiles(row.files) : defaultsFor(catalog)
		);

	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<DetailHeader
					title={row ? row.label : catalog.label}
					eyebrow={eyebrow(record, catalog)}
					onBack={onBack}
					active={!!row && activeFont === row.id}
					canSetActive={hasSelect && !!row}
					onSetActive={() => onSetActive(row.id)}
				/>

				{error && (
					<Notice status="error" isDismissible={false}>
						{error}
					</Notice>
				)}

				{status?.update && (
					<Notice status="warning" isDismissible={false}>
						<strong>
							{sprintf(
								/* translators: 1: installed version, 2: the version waiting */
								__(
									'Update available: %1$s → %2$s',
									'gravity-pdf'
								),
								status.update.installed_version,
								status.update.version
							)}
						</strong>
						{status.update.notes && ` — ${status.update.notes}`}
						{status.update.released &&
							` (${status.update.released})`}
					</Notice>
				)}

				<InstallProgress
					status={status}
					total={catalog.coverage ? catalog.files : 0}
					updating={installed}
					onRetry={() => installEntry(source, entry)}
				/>

				{catalog.coverage ? (
					<Coverage
						catalog={catalog}
						rows={rows}
						activeFont={activeFont}
						hasSelect={hasSelect}
						onSetActive={onSetActive}
					/>
				) : (
					<Display
						catalog={catalog}
						row={row}
						label={label}
						setLabel={setLabel}
						nameError={nameError}
						fontKey={key}
						variants={variants}
						setVariants={setVariants}
					/>
				)}

				{rows.length > 0 && !canDeleteFiles && (
					<div className="section">
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Show on this site', 'gravity-pdf')}
							checked={rows.every((item) => item.enabled)}
							onChange={(enabled) =>
								installEntry(source, entry, { enabled })
							}
						/>
					</div>
				)}
			</div>

			<div className="fm-actions">
				{installed && canDeleteFiles && (
					<Button
						variant="secondary"
						isDestructive
						onClick={() => setConfirm('remove')}
					>
						{row && !catalog.coverage
							? __('Remove this install', 'gravity-pdf')
							: __('Remove', 'gravity-pdf')}
					</Button>
				)}

				<div className="spacer" />

				{row && !catalog.coverage && (
					<Button
						variant="primary"
						isBusy={busy}
						disabled={
							busy ||
							!!nameError ||
							(label === row.label && !rolesChanged)
						}
						onClick={() => applyRow()}
					>
						{rolesChanged
							? __('Apply styles', 'gravity-pdf')
							: __('Save changes', 'gravity-pdf')}
					</Button>
				)}

				{!row && !catalog.coverage && (
					<Button
						variant="primary"
						isBusy={busy}
						disabled={busy || !!nameError || !label.trim()}
						onClick={() =>
							installEntry(source, entry, { label, variants })
						}
					>
						{__('Install', 'gravity-pdf')}
					</Button>
				)}

				{!row && catalog.coverage && !installed && (
					<Button
						variant="primary"
						isBusy={busy || running}
						disabled={busy || running}
						onClick={() => installEntry(source, entry)}
					>
						{sprintf(
							/* translators: %s: how big the pack is, e.g. "10.6 MB" */
							__('Install · %s', 'gravity-pdf'),
							fileSize(catalog.size)
						)}
					</Button>
				)}
			</div>

			{confirm === 'remove' && (
				<ConfirmDialog
					title={__('Remove this font?', 'gravity-pdf')}
					confirmLabel={__('Remove', 'gravity-pdf')}
					destructive
					onCancel={() => setConfirm(null)}
					onConfirm={async () => {
						setConfirm(null);

						if (row && !catalog.coverage) {
							await deleteFont(row.id);
						} else {
							await removeEntry(source, entry);
						}

						onBack();
					}}
				>
					<p>
						{catalog.coverage
							? __(
									'Every font in this pack is removed, and its files are deleted. Text that needed it falls back to the bundled fonts until it is installed again.',
									'gravity-pdf'
								)
							: __(
									'This install is removed. Files another install of the same family still uses are kept.',
									'gravity-pdf'
								)}
					</p>
				</ConfirmDialog>
			)}
		</section>
	);

	async function applyRow() {
		await saveFont(row.id, { label, variants });

		navigate(`/fontmanager/${row.id}`);
	}
}

function Coverage({ catalog, rows, activeFont, hasSelect, onSetActive }) {
	return (
		<>
			<div className="section">
				<p className="section-desc">
					{sprintf(
						/* translators: 1: how many files the pack holds, 2: how big it is */
						__('%1$d files · %2$s', 'gravity-pdf'),
						catalog.files,
						fileSize(catalog.size)
					)}
				</p>
				<ScriptChips scripts={catalog.scripts} />
			</div>

			<div className="section">
				<h3 className="section-title">
					{__('Fonts in this pack', 'gravity-pdf')}
				</h3>

				{rows.length > 0 ? (
					<div className="gfpdf-fm-font-cards">
						{rows.map((item) => (
							<EntryFontCard
								key={item.id}
								row={item}
								active={activeFont === item.id}
								canSetActive={hasSelect}
								onSetActive={onSetActive}
							/>
						))}
					</div>
				) : (
					/* Before an install there are no rows, and the keys are still what the pack is */
					<p className="section-desc">
						{(catalog.font_keys ?? '').split(',').join(', ')}
					</p>
				)}
			</div>
		</>
	);
}

function Display({
	catalog,
	row,
	label,
	setLabel,
	nameError,
	fontKey,
	variants,
	setVariants,
}) {
	return (
		<>
			<div className="section">
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={__('Font name', 'gravity-pdf')}
					value={label}
					onChange={setLabel}
					placeholder={__('e.g. Lato Light', 'gravity-pdf')}
					help={nameError || undefined}
					className={nameError ? 'has-error' : ''}
				/>

				{fontKey && <TemplateUse fontKey={fontKey} />}
			</div>

			<div className="section">
				<h3 className="section-title">
					{__('Font files', 'gravity-pdf')}
				</h3>
				<p className="section-desc">
					{__(
						'Pick which of the family’s weights fills each of the four styles a PDF can use.',
						'gravity-pdf'
					)}
				</p>

				<VariantRoles
					styles={catalog.styles}
					value={variants}
					onChange={setVariants}
				/>
			</div>

			{row && <PreviewSection fontKey={row.id} files={row.files} />}
		</>
	);
}

function eyebrow(record, catalog) {
	return [record?.label, catalog.license].filter(Boolean).join(' · ');
}

/**
 * The role → style mapping an entry installs at when nobody has chosen one
 *
 * Built from the parsed weights rather than a list of Google's tokens, so a source whose styles are `book` and
 * `heavy` still fills Regular and Bold: nearest weight to 400 upright, nearest to 700, and their italics.
 *
 * @param {?Object} catalog The catalogue row
 *
 * @return {Object} Role → style id
 *
 * @since 7.0
 */
function defaultsFor(catalog) {
	const styles = parseStyles(catalog?.styles);

	const nearest = (target, italic) =>
		styles
			.filter((style) => style.italic === italic)
			.sort(
				(a, b) =>
					Math.abs(a.weight - target) - Math.abs(b.weight - target)
			)[0]?.id ?? '';

	return {
		R: nearest(400, false),
		I: nearest(400, true),
		B: nearest(700, false),
		BI: nearest(700, true),
	};
}

/**
 * A catalogue entry that has since been pruned
 *
 * @param {Object}   props
 * @param {Function} props.onBack
 *
 * @return {JSX.Element} The pane
 *
 * @since 7.0
 */
function GoneDetail({ onBack }) {
	return (
		<EmptyState
			text={__('This font is no longer available', 'gravity-pdf')}
		>
			<Button variant="secondary" onClick={onBack}>
				{__('Back to the font list', 'gravity-pdf')}
			</Button>
		</EmptyState>
	);
}

function keyError(label, taken) {
	if (!label.trim()) {
		return '';
	}

	return validateName(label, taken);
}
