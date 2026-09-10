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
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import DetailHeader from './DetailHeader';
import LanguageMapTable from './LanguageMapTable';

/**
 * The scripts a document can be declared to be in
 *
 * mPDF's own `Ucdn::SCRIPT_*` names; the blank option is "derive it from the document language", which is what
 * every site wants until one does not.
 *
 * @since 7.0
 */
const SCRIPTS = [
	'',
	'LATIN',
	'GREEK',
	'CYRILLIC',
	'ARABIC',
	'HEBREW',
	'DEVANAGARI',
	'BENGALI',
	'THAI',
	'HAN',
	'HIRAGANA',
	'KATAKANA',
	'HANGUL',
];

/**
 * Advanced → Language settings
 *
 * Four controls and a table. The rule that makes the table readable is in its help text rather than in a
 * paragraph nobody reads: a row here applies only to text that is *not* in the document's own language.
 *
 * @param {Object}   props
 * @param {Function} props.onBack
 *
 * @return {JSX.Element} The panel
 *
 * @since 7.0
 */
export default function LanguageSettings({ onBack }) {
	const { settings, fonts, busy, error } = useSelect((select) => {
		const store = select(STORE_NAME);

		return {
			settings: store.getSettings(),
			fonts: store.getAllRows(),
			busy: store.isBusy('settings'),
			error: store.getError('settings'),
		};
	}, []);

	const { saveSettings } = useDispatch(STORE_NAME);

	const [draft, setDraft] = useState(null);
	const [overrides, setOverrides] = useState({});
	const [filter, setFilter] = useState('');
	const [adding, setAdding] = useState('');

	useEffect(() => {
		if (settings) {
			setDraft({
				default_pdf_language: settings.default_pdf_language,
				document_script: settings.document_script,
				auto_install_fonts: settings.auto_install_fonts,
			});
			setOverrides({});
		}
	}, [settings]);

	const groups = useMemo(
		() => applyOverrides(settings?.language_map ?? [], overrides),
		[settings, overrides]
	);

	const languages = useMemo(
		() =>
			Object.entries(settings?.labels ?? {}).map(([code, label]) => ({
				value: code,
				label,
			})),
		[settings?.labels]
	);

	/* Both walk every language against every row, and neither depends on the filter box */
	const unused = useMemo(
		() =>
			languages.filter(
				(language) =>
					!groups.some((group) =>
						group.rows.some((row) => row.code === language.value)
					)
			),
		[languages, groups]
	);

	/* Stable, so the memo on each of the ~80 rows can actually hit */
	const onOverride = useCallback(
		(code, font) =>
			setOverrides((previous) => ({ ...previous, [code]: font })),
		[]
	);

	const onRemoveLanguage = useCallback(
		(code) =>
			setOverrides((previous) => {
				const next = { ...previous };
				delete next[code];

				return next;
			}),
		[]
	);

	if (!settings || !draft) {
		return (
			<section className="fm-detail">
				<div className="fm-detail-inner">
					<Spinner />
				</div>
			</section>
		);
	}

	const save = () =>
		saveSettings({
			...draft,
			font_language_overrides: derive(groups),
		});

	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<DetailHeader
					title={__('Language settings', 'gravity-pdf')}
					onBack={onBack}
				/>

				{error && (
					<Notice status="error" isDismissible={false}>
						{error}
					</Notice>
				)}

				<div className="section">
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={__('Default document language', 'gravity-pdf')}
						help={__(
							'What a PDF is assumed to be written in, unless the PDF says otherwise.',
							'gravity-pdf'
						)}
						value={draft.default_pdf_language}
						options={languages}
						onChange={(value) =>
							setDraft({
								...draft,
								default_pdf_language: value,
							})
						}
					/>
				</div>

				<div className="section">
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={__('Document script', 'gravity-pdf')}
						help={__(
							'Leave blank to derive it from the document language.',
							'gravity-pdf'
						)}
						value={draft.document_script}
						options={SCRIPTS.map((script) => ({
							value: script,
							label:
								script ||
								__('Derive automatically', 'gravity-pdf'),
						}))}
						onChange={(value) =>
							setDraft({ ...draft, document_script: value })
						}
					/>
				</div>

				<div className="section">
					<h3 className="section-title">
						{__('Font by language', 'gravity-pdf')}
					</h3>
					<p className="section-desc">
						{__(
							'A row here applies only to text that is not in the document’s own language or script. Character substitution is always on, so a missing glyph is found whether or not a row names it.',
							'gravity-pdf'
						)}
					</p>

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={__('Filter languages', 'gravity-pdf')}
						hideLabelFromVision
						placeholder={__(
							'Filter by name or code…',
							'gravity-pdf'
						)}
						value={filter}
						onChange={setFilter}
					/>

					<LanguageMapTable
						groups={groups}
						fonts={fonts}
						filter={filter}
						onChange={onOverride}
						onRemove={onRemoveLanguage}
					/>

					{unused.length > 0 && (
						<div className="gfpdf-fm-language-add">
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={__('Add a language', 'gravity-pdf')}
								value={adding}
								options={[
									{
										value: '',
										label: __(
											'Choose a language…',
											'gravity-pdf'
										),
									},
									...unused,
								]}
								onChange={setAdding}
							/>
							<Button
								variant="secondary"
								disabled={!adding}
								onClick={() => {
									setOverrides((previous) => ({
										...previous,
										[adding]: '*',
									}));
									setAdding('');
								}}
							>
								{__('Add', 'gravity-pdf')}
							</Button>
						</div>
					)}
				</div>

				<div className="section">
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Install fonts automatically', 'gravity-pdf')}
						help={
							settings.auto_install_locked
								? __(
										'Managed by GPDF_AUTO_INSTALL_FONTS.',
										'gravity-pdf'
									)
								: __(
										'Gravity PDF downloads the packs a PDF needs as it needs them.',
										'gravity-pdf'
									)
						}
						disabled={settings.auto_install_locked}
						checked={draft.auto_install_fonts}
						onChange={(value) =>
							setDraft({ ...draft, auto_install_fonts: value })
						}
					/>
				</div>
			</div>

			<div className="fm-actions">
				<div className="spacer" />
				<Button
					variant="primary"
					isBusy={busy}
					disabled={busy}
					onClick={save}
				>
					{__('Save settings', 'gravity-pdf')}
				</Button>
			</div>
		</section>
	);
}

/**
 * Fold the unsaved edits into the map the server sent
 *
 * A code the server never listed becomes an "Other languages" row, which is how "Add a language" shows up
 * before there is anything to save.
 *
 * @param {Array<Object>} groups
 * @param {Object}        overrides
 *
 * @return {Array<Object>} The map as the table should draw it
 *
 * @since 7.0
 */
export function applyOverrides(groups, overrides) {
	const known = new Set(
		groups.flatMap((group) => group.rows.map((row) => row.code))
	);

	const merged = groups.map((group) => ({
		...group,
		rows: group.rows.map((row) =>
			overrides[row.code] !== undefined
				? { ...row, font: overrides[row.code] }
				: row
		),
	}));

	const added = Object.keys(overrides).filter((code) => !known.has(code));

	if (added.length === 0) {
		return merged;
	}

	const other = merged.find((group) => group.group === 'other');
	const rows = added.map((code) => ({
		code,
		label: code,
		default_font: '*',
		font: overrides[code],
	}));

	if (other) {
		other.rows = [...other.rows, ...rows];

		return merged;
	}

	return [
		...merged,
		{ group: 'other', label: __('Other languages', 'gravity-pdf'), rows },
	];
}

/**
 * The overrides worth saving: the rows that differ from the default
 *
 * The server prunes again on the way in — this is the same rule, applied early so the request is small.
 *
 * @param {Array<Object>} groups
 *
 * @return {Object} code → font key
 *
 * @since 7.0
 */
export function derive(groups) {
	return groups
		.flatMap((group) => group.rows)
		.filter((row) => row.font !== row.default_font)
		.reduce((map, row) => {
			map[row.code] = row.font;

			return map;
		}, {});
}
