/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { PACK_ENTRIES, SOURCE_RECORDS } from './catalog';

/**
 * The mocked server's memory
 *
 * Everything the routes read and write lives here for the life of the page: font rows, the catalogue's status
 * columns and the settings blob. Installs run on timers so progress is something the poller can actually watch,
 * which is the only reason a mock of this depth is worth having.
 *
 * @since 7.0
 */

const DAY = 86400000;

const iso = (offset = 0) => new Date(Date.now() - offset).toISOString();

/**
 * How long each file of an install "downloads" for
 *
 * @since 7.0
 */
export const FILE_TICK = 550;

const bundledFile = (role, name) => ({
	role,
	variant: null,
	path: name,
	url: `${pluginUrl()}fonts/${name}`,
	size: 333961,
	missing: 0,
});

function pluginUrl() {
	return (typeof GFPDF !== 'undefined' && GFPDF.pluginUrl) || '/';
}

const filesFor = (roles, prefix, variants = {}) =>
	roles.reduce((files, role) => {
		files[role] = {
			role,
			variant: variants[role] ?? null,
			path: `${prefix}-${role}.ttf`,
			url: null,
			size: 180000,
			missing: 0,
		};

		return files;
	}, {});

/**
 * The bundled faces: not rows, and never installed or removed
 *
 * @since 7.0
 */
export const BUNDLED = [
	{
		id: 'gfpdf-arimo',
		label: 'Arimo',
		source: 'bundled',
		entry: null,
		coverage: 0,
		version: null,
		enabled: true,
		description:
			'Ships with Gravity PDF and covers Latin, Greek, Cyrillic and Hebrew. Metric-compatible with Arial, so a template written for Arial keeps its line breaks.',
		files: {
			R: bundledFile('R', 'Arimo-Regular.ttf'),
			B: bundledFile('B', 'Arimo-Bold.ttf'),
			I: bundledFile('I', 'Arimo-Italic.ttf'),
			BI: bundledFile('BI', 'Arimo-BoldItalic.ttf'),
		},
	},
];

/**
 * Build the state a fresh page load starts from
 *
 * Exported so a test can reset between cases rather than reaching into the live object.
 *
 * @return {Object} The mocked server state
 *
 * @since 7.0
 */
export function initialState() {
	return {
		rows: [
			{
				id: 'notoemoji',
				label: 'Noto Emoji',
				source: 'packs',
				entry: 'emoji',
				coverage: 1,
				version: '1.0.0',
				enabled: true,
				files: {
					...filesFor(['R'], 'NotoEmoji'),
					...filesFor(['license'], 'NotoEmoji-OFL'),
				},
			},
			{
				id: 'signaturescript',
				label: 'Signature Script',
				source: 'custom',
				entry: null,
				coverage: 0,
				version: null,
				enabled: true,
				files: filesFor(['R'], 'SignatureScript'),
			},
			{
				id: 'brandsans',
				label: 'Brand Sans',
				source: 'custom',
				entry: null,
				coverage: 0,
				version: null,
				enabled: true,
				files: filesFor(['R', 'B', 'I', 'BI'], 'BrandSans'),
			},
			{
				id: 'lato',
				label: 'Lato',
				source: 'google',
				entry: 'lato',
				coverage: 0,
				version: 'v29',
				enabled: true,
				files: filesFor(['R', 'B', 'I', 'BI'], 'Lato', {
					R: 'regular',
					B: '700',
					I: 'italic',
					BI: '700italic',
				}),
			},
		],

		/* Keyed `source/entry`; only entries that have been touched carry a record */
		status: {},

		/* Per source, the option record `GET /fonts/sources` reports */
		sync: {
			packs: {
				synced: iso(3 * DAY),
				last_attempt: iso(3 * DAY),
				last_error: '',
			},
			google: {
				synced: iso(61 * DAY),
				last_attempt: iso(2 * DAY),
				last_error: 'The catalogue server did not respond in time.',
			},
		},

		settings: {
			default_pdf_language: 'en',
			document_script: '',
			auto_install_fonts: true,
			auto_install_locked: false,
		},

		/* code → font key, the overrides the settings screen posts back */
		overrides: {},

		timers: [],
	};
}

export const state = initialState();

/**
 * Throw away every scheduled install and start again
 *
 * @since 7.0
 */
export function resetState() {
	state.timers.forEach((timer) => clearTimeout(timer));

	Object.assign(state, initialState());
}

/**
 * The catalogue row for an id, whatever source it belongs to
 *
 * @param {string} source
 * @param {string} entry
 *
 * @return {?Object} The row, or null when the catalogue has no such entry
 *
 * @since 7.0
 */
export function catalogEntry(source, entry) {
	const record = SOURCE_RECORDS.find((item) => item.id === source);

	return record?.entries.find((row) => row.entry === entry) ?? null;
}

/**
 * The rows one entry installed
 *
 * @param {string} source
 * @param {string} entry
 *
 * @return {Array<Object>} The font rows
 *
 * @since 7.0
 */
export function entryRows(source, entry) {
	return state.rows.filter(
		(row) => row.source === source && row.entry === entry
	);
}

/**
 * Set the status columns of one catalogue row
 *
 * @param {string} id     `source/entry`
 * @param {Object} fields The columns to write
 *
 * @since 7.0
 */
export function setStatus(id, fields) {
	state.status[id] = { ...(state.status[id] ?? {}), ...fields };
}

/**
 * Compose the status object for one entry, exactly as `Registry::get_install_status()` does
 *
 * @param {string} id `source/entry`
 *
 * @return {Object} The status object
 *
 * @since 7.0
 */
export function installStatus(id) {
	const [source, entry] = id.split('/');
	const row = catalogEntry(source, entry);
	const rows = entryRows(source, entry);
	const stored = state.status[id] ?? {};

	const paths = new Set();
	rows.forEach((item) =>
		Object.values(item.files).forEach((file) => paths.add(file.path))
	);

	const installed = rows.length > 0;
	const updateAvailable =
		installed && rows.some((item) => item.version !== row?.version);

	const status = {
		phase: stored.phase ?? null,
		files_done: stored.files_done ?? paths.size,
		installed,
		update_available: updateAvailable,
	};

	if (stored.error) {
		status.error = stored.error;
	}

	if (stored.stuck) {
		status.stuck = true;
	}

	if (updateAvailable && row) {
		status.update = {
			installed_version: rows[0].version,
			version: row.version,
			notes: row.notes,
			released: row.released,
			files: row.files,
			size: row.size,
		};
	}

	return status;
}

/**
 * Every entry the status route reports: those with rows, and those mid-install
 *
 * @return {Object} `source/entry` → status object
 *
 * @since 7.0
 */
export function allStatuses() {
	const ids = new Set(Object.keys(state.status));

	state.rows
		.filter((row) => row.entry)
		.forEach((row) => ids.add(`${row.source}/${row.entry}`));

	return [...ids].reduce((map, id) => {
		map[id] = installStatus(id);

		return map;
	}, {});
}

/**
 * The `always` entries no row covers — the Bundled panel's warning list
 *
 * @return {Array<Object>} `{ source, entry, label, size }` per missing entry
 *
 * @since 7.0
 */
export function missingAlways() {
	return PACK_ENTRIES.filter(
		(row) =>
			row.always &&
			entryRows(row.source, row.entry).length === 0 &&
			state.status[`${row.source}/${row.entry}`]?.phase !== 'removed'
	).map((row) => ({
		source: row.source,
		entry: row.entry,
		label: row.label,
		size: row.size,
	}));
}

/**
 * Walk an install through its files, one tick each, then write its rows
 *
 * The phase transitions and the timing are the only part of the installer worth mocking: they are what the
 * poller and every progress bar in the UI read.
 *
 * @param {string}  id       `source/entry`
 * @param {string}  label    The key the install is filed under
 * @param {?Object} variants Role → style id, when the form chose them
 *
 * @since 7.0
 */
export function runInstall(id, label, variants) {
	const [source, entry] = id.split('/');
	const row = catalogEntry(source, entry);

	if (!row) {
		return;
	}

	setStatus(id, {
		phase: 'queued',
		phase_since: iso(),
		files_done: 0,
		error: null,
	});

	for (let done = 1; done <= row.files; done++) {
		state.timers.push(
			setTimeout(() => {
				setStatus(id, { phase: 'installing', files_done: done });
			}, FILE_TICK * done)
		);
	}

	state.timers.push(
		setTimeout(
			() => finishInstall(id, row, label, variants),
			FILE_TICK * (row.files + 1)
		)
	);
}

function finishInstall(id, row, label, variants) {
	const keys = row.font_keys ? row.font_keys.split(',') : [row.entry];
	const roles = row.styles
		? rolesFromVariants(variants, row)
		: ['R', 'B', 'I', 'BI'];

	if (label) {
		upsertRow(label, row, roles, variants);
	} else {
		keys.forEach((key) => upsertRow(key, row, roles, variants));
	}

	setStatus(id, {
		phase: null,
		phase_since: null,
		files_done: undefined,
		error: null,
	});
}

function rolesFromVariants(variants, row) {
	const available = row.styles.split(',');
	const chosen = variants ?? defaultVariants(available);

	return Object.keys(chosen).filter((role) => chosen[role]);
}

/**
 * The role → style mapping an entry installs at when nobody chose one
 *
 * @param {Array<string>} styles The entry's style ids
 *
 * @return {Object} Role → style id
 *
 * @since 7.0
 */
export function defaultVariants(styles) {
	const pick = (...candidates) =>
		candidates.find((candidate) => styles.includes(candidate)) ?? '';

	return {
		R: pick('regular', '400', '300', '500'),
		I: pick('italic', '400italic'),
		B: pick('700', 'bold', '600', '800', '900'),
		BI: pick('700italic', '600italic', '900italic'),
	};
}

function upsertRow(key, row, roles, variants) {
	const existing = state.rows.find((item) => item.id === key);
	const chosen =
		variants ?? (row.styles ? defaultVariants(row.styles.split(',')) : {});

	const next = {
		id: key,
		label: labelFor(key, row),
		source: row.source,
		entry: row.entry,
		coverage: row.coverage,
		version: row.version,
		enabled: true,
		files: filesFor(
			roles,
			key.replace(/(^|-)([a-z])/g, (match, sep, letter) =>
				letter.toUpperCase()
			),
			chosen
		),
	};

	if (existing) {
		Object.assign(existing, next, { label: existing.label });

		return;
	}

	state.rows.push(next);
}

function labelFor(key, row) {
	const keys = row.font_keys ? row.font_keys.split(',') : [];

	return keys.length > 1 ? titleCase(key) : row.label;
}

function titleCase(key) {
	return key
		.split('-')
		.map((part) => part.charAt(0).toUpperCase() + part.slice(1))
		.join(' ');
}

export { iso, filesFor };
