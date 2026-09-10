/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import { isInstalling } from '../utils/install';
import { paths, selectionFrom } from '../utils/paths';
import AddFontMenu from './AddFontMenu';
import FontRow from './FontRow';
import ScriptChips from './ScriptChips';
import SearchBox from './SearchBox';

/**
 * The left pane: search, the Add menu, the updates banner, and three groups of rows
 *
 * Bundled, Custom and Language packs — the same three groups the settings dropdown emits as optgroups, so an
 * admin who found a font in one list finds it in the same place in the other.
 *
 * @param {Object}   props
 * @param {?Object}  props.route    The matched route, for the selected row
 * @param {Function} props.navigate
 * @param {Function} props.onUpload
 * @param {boolean}  props.adding   Whether an unsaved upload is showing in the detail pane
 *
 * @return {JSX.Element} The sidebar
 *
 * @since 7.0
 */
export default function FontSidebar({ route, navigate, onUpload, adding }) {
	const [search, setSearch] = useState('');

	const {
		bundled,
		custom,
		groups,
		sources,
		sourceLabels,
		activeFont,
		statuses,
		updates,
	} = useSelect((select) => {
		const store = select(STORE_NAME);

		return {
			bundled: store.getBundledFonts(),
			custom: store.getCustomFonts(),
			groups: store.getFontGroups(),
			sources: store.getSources() ?? [],
			sourceLabels: store.getSourceLabels(),
			activeFont: store.getActiveFont(),
			statuses: store.getStatuses(),
			updates: store.getUpdates(),
		};
	}, []);

	const matches = (label, source) => {
		const term = search.trim().toLowerCase();

		if (!term) {
			return true;
		}

		return (
			label.toLowerCase().includes(term) ||
			(sourceLabels[source] ?? '').toLowerCase().includes(term)
		);
	};

	const customRows = custom.filter((row) => matches(row.label, row.source));
	const packRows = groups.filter((group) =>
		matches(group.label, group.source)
	);
	const bundledRows = bundled.filter((row) => matches(row.label, 'bundled'));

	const selected = selectionFrom(route);

	const empty =
		bundledRows.length + customRows.length + packRows.length === 0;

	return (
		<aside className="fm-sidebar">
			<div className="fm-sidebar-top">
				<SearchBox
					value={search}
					onChange={setSearch}
					label={__('Search fonts', 'gravity-pdf')}
					placeholder={__('Search fonts…', 'gravity-pdf')}
				/>

				<AddFontMenu
					sources={sources}
					onUpload={onUpload}
					onBrowse={(id) => navigate(paths.browse(id))}
				/>

				{updates.length > 0 && (
					<Button
						variant="tertiary"
						className="gfpdf-fm-updates-banner"
						onClick={() => navigate(paths.updates())}
					>
						{sprintf(
							/* translators: %d: how many fonts have an update waiting */
							_n(
								'%d update available · Update all',
								'%d updates available · Update all',
								updates.length,
								'gravity-pdf'
							),
							updates.length
						)}
					</Button>
				)}
			</div>

			<div className="fm-list">
				{adding && (
					<FontRow
						id="new"
						label={__('New font', 'gravity-pdf')}
						sub={__('Not saved yet', 'gravity-pdf')}
						selected
						onSelect={onUpload}
					/>
				)}

				{empty && (
					<div className="fm-empty-text">
						{__('No results.', 'gravity-pdf')}
					</div>
				)}

				<Group label={__('Bundled', 'gravity-pdf')} rows={bundledRows}>
					{(row) => (
						<FontRow
							key={row.id}
							id={row.id}
							label={row.label}
							sub={__('Ships with Gravity PDF', 'gravity-pdf')}
							selected={selected.id === row.id}
							active={activeFont === row.id}
							onSelect={() => navigate(paths.font(row.id))}
						/>
					)}
				</Group>

				<Group label={__('Custom', 'gravity-pdf')} rows={customRows}>
					{(row) => (
						<FontRow
							key={row.id}
							id={row.id}
							label={row.label}
							preview={row.preview_url ?? null}
							sub={customSub(row, sourceLabels)}
							selected={selected.id === row.id}
							active={activeFont === row.id}
							dimmed={!row.enabled}
							onSelect={() => navigate(paths.font(row.id))}
						/>
					)}
				</Group>

				<Group
					label={__('Language packs', 'gravity-pdf')}
					rows={packRows}
				>
					{(group) => {
						const id = `${group.source}/${group.entry}`;

						return (
							<FontRow
								key={id}
								id={id}
								label={group.label}
								scripts={group.scripts}
								sub={packSub(group, statuses[id])}
								selected={selected.entry === id}
								onSelect={() =>
									navigate(
										paths.entry(group.source, group.entry)
									)
								}
							/>
						);
					}}
				</Group>
			</div>
		</aside>
	);
}

function Group({ label, rows, children }) {
	if (rows.length === 0) {
		return null;
	}

	return (
		<>
			<h3 className="fm-list-heading">{label}</h3>
			{rows.map(children)}
		</>
	);
}

function customSub(row, sourceLabels) {
	const count = Object.keys(row.files ?? {}).length;
	const variants = sprintf(
		/* translators: %d: how many of the four faces this font has files for */
		__('%d/4 variants', 'gravity-pdf'),
		count
	);

	const source = sourceLabels[row.source];

	return source ? `${variants} · ${source}` : variants;
}

function packSub(group, status) {
	const done = status?.files_done ?? 0;

	if (isInstalling(status)) {
		return sprintf(
			/* translators: 1: files downloaded so far, 2: how many the pack holds */
			__('Installing · %1$d of %2$d files', 'gravity-pdf'),
			done,
			group.files
		);
	}

	if (group.files && done < group.files) {
		return sprintf(
			/* translators: 1: files present, 2: how many the pack holds */
			__('%1$d of %2$d files', 'gravity-pdf'),
			done,
			group.files
		);
	}

	return <ScriptChips scripts={group.scripts} limit={3} />;
}
