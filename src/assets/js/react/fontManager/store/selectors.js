/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { createRegistrySelector, createSelector } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import { searchKey } from '../utils/searchKey';

/**
 * Everything the UI reads
 *
 * No component fetches: it selects. Two rules run through the file. Anything derived from the font list or the
 * source records goes through `getFonts()` / `getSources()` as a **registry selector**, so reading the slice a
 * pane happens to need is what resolves the request — no component has to select something it does not use in
 * order to prime a resolver. And anything that derives a list is wrapped in `createSelector` keyed on the
 * slice it reads, because `useSelect` compares what it maps shallowly and a fresh array re-renders every
 * subscriber on every unrelated write.
 *
 * @since 7.0
 */

const EMPTY = [];

export const getFonts = (state) => state.fonts;
export const getSources = (state) => state.sources;
export const getSettings = (state) => state.settings;
export const getStatuses = (state) => state.statuses;
export const getActiveFont = (state) => state.activeFont;

export const isBusy = (state, key) => !!state.busy[key];
export const getError = (state, key) => state.errors[key] ?? '';

const fromFonts = (derive) =>
	createRegistrySelector((select) => (state, ...args) => {
		const fonts = select(STORE_NAME).getFonts();

		return derive(fonts, ...args);
	});

const fromSources = (derive) =>
	createRegistrySelector((select) => (state, ...args) => {
		const sources = select(STORE_NAME).getSources();

		return derive(sources ?? EMPTY, ...args);
	});

export const getBundledFonts = fromFonts((fonts) => fonts?.bundled ?? EMPTY);
export const getFontGroups = fromFonts((fonts) => fonts?.groups ?? EMPTY);
export const getCustomFonts = fromFonts((fonts) => fonts?.custom ?? EMPTY);
export const getMissingEntries = fromFonts((fonts) => fonts?.missing ?? EMPTY);
export const canDeleteFiles = fromFonts((fonts) => !!fonts?.can_delete_files);

/**
 * Every installed row, bundled faces included, in sidebar order
 *
 * Each row is tagged with the bucket it came from, because that is the fact the detail pane needs and the only
 * other way to recover it is to compare `source` against the literal `'bundled'` — a word third-party sources
 * register into.
 *
 * @since 7.0
 */
const flatten = createSelector(
	(fonts) => {
		if (!fonts) {
			return EMPTY;
		}

		return [
			...fonts.bundled.map((row) => ({ ...row, kind: 'bundled' })),
			...fonts.custom.map((row) => ({
				...row,
				kind: row.entry ? 'entry' : 'custom',
			})),
			...fonts.groups.flatMap((group) =>
				group.fonts.map((row) => ({ ...row, kind: 'entry' }))
			),
		];
	},
	(fonts) => [fonts]
);

export const getAllRows = fromFonts(flatten);

export const getRow = fromFonts(
	(fonts, id) => flatten(fonts).find((row) => row.id === id) ?? null
);

export const getEntryRows = fromFonts(
	createSelector(
		(fonts, source, entry) =>
			flatten(fonts).filter(
				(row) => row.source === source && row.entry === entry
			),
		(fonts) => [fonts]
	)
);

/**
 * The font keys already in use, minus the row being edited
 *
 * The rule the name field validates against, in the one place that knows every row.
 *
 * @since 7.0
 */
export const getTakenKeys = fromFonts(
	createSelector(
		(fonts, exclude = '') =>
			flatten(fonts)
				.filter((row) => row.id !== exclude)
				.map((row) => row.id),
		(fonts) => [fonts]
	)
);

/**
 * The status object for one entry: the only source of progress and installed state
 *
 * @param {Object} state
 * @param {string} id    `source/entry`
 *
 * @return {?Object} The status object, or null when the entry has never been touched
 *
 * @since 7.0
 */
export const getInstallStatus = (state, id) => state.statuses[id] ?? null;

export const getSource = fromSources(
	(sources, id) => sources.find((source) => source.id === id) ?? null
);

/**
 * Source id → label, for the surfaces that name a row's source rather than open it
 *
 * @since 7.0
 */
export const getSourceLabels = fromSources(
	createSelector(
		(sources) =>
			sources.reduce((map, source) => {
				map[source.id] = source.label;

				return map;
			}, {}),
		(sources) => [sources]
	)
);

/**
 * What the header's one sync line says
 *
 * @since 7.0
 */
export const getSyncSummary = fromSources(
	createSelector(
		(sources) => {
			if (sources.length === 0) {
				return {
					synced: null,
					never: false,
					stale: false,
					failed: false,
				};
			}

			const dates = sources
				.filter((source) => source.synced)
				.map((source) => source.synced)
				.sort();

			return {
				synced: dates[0] ?? null,
				never: sources.some((source) => !source.synced),
				stale: sources.some((source) => source.stale),
				failed: sources.some(
					(source) =>
						source.last_error &&
						(!source.synced ||
							Date.parse(source.last_attempt) >
								Date.parse(source.synced))
				),
			};
		},
		(sources) => [sources]
	)
);

/**
 * Every entry with an update waiting, from the status objects alone
 *
 * @since 7.0
 */
export const getUpdates = createSelector(
	(state) =>
		Object.entries(state.statuses)
			.filter(([, status]) => status.update)
			.map(([id, status]) => {
				const [source, entry] = id.split('/');

				return { id, source, entry, ...status.update };
			}),
	(state) => [state.statuses]
);

/**
 * One catalogue row, as `GET /fonts/sources/{source}/{entry}` returned it
 *
 * @param {Object} state
 * @param {string} source
 * @param {string} entry
 *
 * @return {?Object} The row, or `false` once the catalogue has answered that it is gone
 *
 * @since 7.0
 */
export const getEntry = (state, source, entry) =>
	state.entries[`${source}/${entry}`] ?? null;

/**
 * One page of a source search
 *
 * @param {Object} state
 * @param {string} source
 * @param {Object} query  The `s` / `category` / `subset` / `coverage` / `page` params
 *
 * @return {?Object} `{ entries, total, pages, synced }`
 *
 * @since 7.0
 */
export const getSourceEntries = (state, source, query) =>
	state.searches[searchKey(source, query)] ?? null;
