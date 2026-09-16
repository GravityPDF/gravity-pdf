/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { PACK_ENTRIES, SOURCE_RECORDS } from './catalog';
import {
	BUNDLED,
	allStatuses,
	catalogEntry,
	entryRows,
	installStatus,
	missingAlways,
	runInstall,
	setStatus,
	state,
} from './state';
import {
	PER_PAGE,
	ROLES,
} from '../../../../../src/assets/js/react/fontManager/constants';

/**
 * A stand-in for the whole `/fonts` namespace, for the Jest suite alone
 *
 * Registered as an `apiFetch` middleware, so every component and resolver calls the real path it calls in the
 * browser and no component knows this file exists. Requests to anything else fall straight through.
 *
 * The plugin now serves every route in the table — this is the test seam, not a shim: a component test that had
 * to stand up REST fixtures would be testing WordPress.
 *
 * @since 7.0
 */

const LATENCY = 220;

const ROUTES = [
	['GET', /^\/fonts\/?$/, fonts],
	['POST', /^\/fonts\/?$/, uploadFont],
	['GET', /^\/fonts\/sources\/?$/, sources],
	['POST', /^\/fonts\/sources\/sync\/?$/, sync],
	['GET', /^\/fonts\/sources\/([a-z0-9-]+)\/?$/, searchSource],
	['GET', /^\/fonts\/sources\/([a-z0-9-]+)\/([a-z0-9-]+)\/?$/, sourceEntry],
	['POST', /^\/fonts\/sources\/([a-z0-9-]+)\/([a-z0-9-]+)\/?$/, install],
	[
		'DELETE',
		/^\/fonts\/sources\/([a-z0-9-]+)\/([a-z0-9-]+)\/?$/,
		removeEntry,
	],
	['GET', /^\/fonts\/status\/?$/, status],
	['POST', /^\/fonts\/updates\/?$/, updateAll],
	['POST', /^\/fonts\/import\/?$/, importPackage],
	['GET', /^\/fonts\/settings\/?$/, readSettings],
	['POST', /^\/fonts\/settings\/?$/, writeSettings],
	['POST', /^\/fonts\/([a-z0-9_-]+)\/?$/, editFont],
	['DELETE', /^\/fonts\/([a-z0-9_-]+)\/?$/, deleteFont],
];

/**
 * Answer one request, or hand it back for the next middleware
 *
 * @param {Object}   options The `apiFetch` options
 * @param {Function} next    The rest of the chain
 *
 * @return {Promise} The mocked response, or whatever the chain returns
 *
 * @since 7.0
 */
export function mockMiddleware(options, next) {
	const url = options.path ?? '';

	if (!url.startsWith('/gravity-pdf/v1/fonts')) {
		return next(options);
	}

	const [path, query = ''] = url.slice('/gravity-pdf/v1'.length).split('?');

	const method = (options.method ?? 'GET').toUpperCase();

	for (const [verb, pattern, handler] of ROUTES) {
		const match = verb === method && path.match(pattern);

		if (match) {
			return respond(() =>
				handler(
					{
						params: match.slice(1),
						query: new URLSearchParams(query),
						data: options.data ?? {},
					},
					options
				)
			);
		}
	}

	return Promise.reject(
		error('rest_no_route', 'No route was found matching the URL', 404)
	);
}

function respond(run) {
	return new Promise((resolve, reject) => {
		setTimeout(() => {
			try {
				/*
				 * Serialised, because a real response is a fresh document every time and handing back the
				 * server's own objects makes the store's identity checks pass for a reason production does not
				 * have — which hid a panel losing an in-progress edit on every background refresh
				 */
				resolve(JSON.parse(JSON.stringify(run())));
			} catch (failure) {
				reject(failure);
			}
		}, LATENCY);
	});
}

function error(code, message, status_code) {
	return { code, message, data: { status: status_code } };
}

/* --- /fonts --- */

function fonts() {
	const rows = state.rows;
	const groups = PACK_ENTRIES.filter(
		(entry) => entryRows(entry.source, entry.entry).length > 0
	).map((entry) => ({
		label: entry.label,
		source: entry.source,
		entry: entry.entry,
		/* The sidebar's "N of M files" needs the total, and the status object deliberately carries only N */
		files: entry.files,
		scripts: entry.scripts,
		fonts: entryRows(entry.source, entry.entry),
	}));

	return {
		bundled: BUNDLED,
		groups,
		custom: rows.filter((row) => !row.coverage),
		missing: missingAlways(),
		can_delete_files: true,
	};
}

/**
 * The parts of a custom-font write, however it was sent
 *
 * A write carrying faces is multipart and a write carrying `variants` or `enabled` is JSON, so the two arrive
 * in different halves of the request. Faces come back under the ROLE ids the rest of the mock speaks, which is
 * the translation `Rest_Custom_Fonts` does with `Font_Repository::LEGACY_FACE_ROLES`.
 *
 * @param {Object} request The matched request
 * @param {Object} options The raw `apiFetch` options
 *
 * @return {Object} `{ fields, faces }`
 *
 * @since 7.0
 */
function fontWrite(request, options) {
	if (!(options.body instanceof window.FormData)) {
		return { fields: request.data, faces: {} };
	}

	const fields = {};
	const faces = {};

	options.body.forEach((value, name) => {
		const role = ROLES.find((item) => item.field === name);

		if (!role) {
			fields[name] = value;

			return;
		}

		/* A File replaces the face; the empty string the route reads as "clear it" */
		faces[role.id] = value instanceof window.File ? value.name : null;
	});

	return { fields, faces };
}

function uploadFont(request, options) {
	const { fields, faces } = fontWrite(request, options);

	/* `add_item()`'s one required part, and the check that fails a write whose files never left the browser */
	if (!faces.R) {
		throw error(
			'font_validation_error',
			{ regular: 'The Regular font is required' },
			400
		);
	}

	const key = uniqueKey(keyFromLabel(fields.label ?? 'New font'));

	const row = {
		id: key,
		label: fields.label ?? 'New font',
		source: 'custom',
		entry: null,
		coverage: 0,
		version: null,
		enabled: true,
		files: rolesToFiles(faces),
	};

	state.rows.push(row);

	return state.uploadWarnings.length
		? { ...row, warnings: state.uploadWarnings }
		: row;
}

function editFont(request, options) {
	const { params } = request;
	const row = state.rows.find((item) => item.id === params[0]);

	if (!row) {
		throw error('invalid_font_id', 'The font could not be found', 400);
	}

	const { fields: data, faces } = fontWrite(request, options);

	if (data.label !== undefined) {
		row.label = data.label;
	}

	if (data.enabled !== undefined) {
		row.enabled = !!data.enabled;
	}

	/* Every named face is cleared first, then the ones carrying a file are written back — `rolesToFiles()`
	   drops the empty ones, which is the same rule the route's two passes apply */
	Object.keys(faces).forEach((role) => delete row.files[role]);

	row.files = { ...row.files, ...rolesToFiles(faces) };

	if (data.variants) {
		Object.entries(data.variants).forEach(([role, variant]) => {
			if (!variant) {
				delete row.files[role];

				return;
			}

			row.files[role] = {
				role,
				variant,
				path: `${row.id}-${variant}.ttf`,
				url: null,
				size: 180000,
				missing: 0,
			};
		});
	}

	return row;
}

function deleteFont({ params }) {
	const index = state.rows.findIndex((item) => item.id === params[0]);

	if (index === -1) {
		throw error('invalid_font_id', 'The font could not be found', 400);
	}

	if (state.rows[index].coverage) {
		throw error(
			'font_owned_by_entry',
			'This font belongs to a language pack. Remove the pack instead.',
			400
		);
	}

	state.rows.splice(index, 1);

	return { deleted: true, id: params[0] };
}

/* --- /fonts/sources --- */

function sources() {
	return SOURCE_RECORDS.map((record) => {
		const record_sync = state.sync[record.id] ?? {};

		return {
			id: record.id,
			label: record.label,
			description: record.description,
			total: record.entries.length,
			/* A count, as the route returns: how many of this source's entries are language packs */
			coverage: record.entries.filter((entry) => entry.coverage).length,
			filters: filtersFor(record.entries),
			synced: record_sync.synced ?? null,
			stale: isStale(record_sync.synced),
			last_attempt: record_sync.last_attempt ?? null,
			last_error: record_sync.last_error ?? '',
		};
	});
}

function sync() {
	const stale = sources().filter((source) => source.stale);

	if (stale.length === 0) {
		return { up_to_date: true };
	}

	stale.forEach((source) => {
		state.timers.push(
			setTimeout(() => {
				state.sync[source.id] = {
					synced: new Date().toISOString(),
					last_attempt: new Date().toISOString(),
					last_error: '',
				};
			}, 1500)
		);
	});

	return { queued: stale.map((source) => source.id) };
}

function searchSource({ params, query }) {
	const record = SOURCE_RECORDS.find((item) => item.id === params[0]);

	if (!record) {
		throw error('font_source_unknown', 'Unknown font source', 404);
	}

	const search = (query.get('s') ?? '').trim().toLowerCase();
	const category = query.get('category') ?? '';
	const subset = query.get('subset') ?? '';
	const coverage = query.get('coverage') ?? '';
	const page = Math.max(1, parseInt(query.get('page') ?? '1', 10));

	const matches = record.entries.filter((row) => {
		if (search && !row.label.toLowerCase().includes(search)) {
			return false;
		}

		if (category && row.category !== category) {
			return false;
		}

		if (subset && !(row.subsets ?? '').split(',').includes(subset)) {
			return false;
		}

		return !(coverage === '1' && !row.coverage);
	});

	return {
		entries: matches.slice((page - 1) * PER_PAGE, page * PER_PAGE),
		total: matches.length,
		pages: Math.max(1, Math.ceil(matches.length / PER_PAGE)),
		synced: state.sync[record.id]?.synced ?? null,
	};
}

function sourceEntry({ params }) {
	const row = catalogEntry(params[0], params[1]);

	if (!row) {
		throw error('font_entry_unknown', 'Unknown catalogue entry', 404);
	}

	return { ...row, preview_urls: null };
}

function install({ params, data }) {
	const id = params.join('/');

	if (!catalogEntry(params[0], params[1])) {
		throw error('font_entry_unknown', 'Unknown catalogue entry', 404);
	}

	if (data.enabled !== undefined) {
		entryRows(params[0], params[1]).forEach((row) => {
			row.enabled = !!data.enabled;
		});

		return { [id]: installStatus(id) };
	}

	runInstall(
		id,
		data.label ? keyFromLabel(data.label) : '',
		data.variants ?? null
	);

	return { [id]: installStatus(id) };
}

function removeEntry({ params }) {
	const id = params.join('/');
	const rows = entryRows(params[0], params[1]);

	rows.forEach((row) => {
		state.rows.splice(state.rows.indexOf(row), 1);
	});

	const entry = catalogEntry(params[0], params[1]);

	setStatus(id, {
		phase: entry?.always ? 'removed' : null,
		files_done: 0,
		error: null,
	});

	return { [id]: installStatus(id) };
}

/* --- /fonts/import --- */

/* The mocked STORE's own naming rule, and the only copy of it in the front end: the plugin matches an upload
   against the `package` column rather than rebuilding a filename, so a test hard-coding a name instead would be
   asserting against the wrong side of that boundary */
export const archiveName = (pack) => `${pack.entry}-fonts-v${pack.version}.zip`;

function importPackage(request, options) {
	const file = options.body?.get('file');

	if (!file) {
		throw error(
			'font_package_missing',
			'No font package was uploaded.',
			400
		);
	}

	if (file.size > state.upload_cap) {
		throw error(
			'font_package_too_large',
			'The font package is larger than this server accepts as an upload. Ask your host to raise the upload limit, or copy the font files into the fonts directory over FTP instead.',
			413
		);
	}

	const pack = PACK_ENTRIES.find((entry) => archiveName(entry) === file.name);

	if (!pack) {
		throw error(
			'font_entry_unknown',
			'The font catalogue does not list this package. Sync the catalogue and try again — a site that has never synced cannot verify an import yet.',
			404
		);
	}

	const id = `${pack.source}/${pack.entry}`;

	/* The files are on disk by now, so what follows is the ordinary install every other trigger queues */
	runInstall(id, '', null);

	return { [id]: installStatus(id) };
}

/* --- /fonts/status, /fonts/updates, /fonts/settings --- */

function status() {
	return allStatuses();
}

function updateAll() {
	const pending = Object.entries(allStatuses()).filter(
		([, item]) => item.update
	);

	pending.forEach(([id]) => runInstall(id, '', null));

	return { queued: pending.map(([id]) => id) };
}

function readSettings() {
	return {
		...state.settings,
		language_map: languageMap(),
		labels: LANGUAGE_LABELS,
		scripts: SCRIPTS,
	};
}

function writeSettings({ data }) {
	Object.assign(state.settings, {
		default_pdf_language:
			data.default_pdf_language ?? state.settings.default_pdf_language,
		document_script: data.document_script ?? state.settings.document_script,
		auto_install_fonts:
			data.auto_install_fonts ?? state.settings.auto_install_fonts,
	});

	if (data.font_language_overrides) {
		state.overrides = { ...data.font_language_overrides };
	}

	return readSettings();
}

/* --- helpers --- */

/* A shortlist, like `Registry::scripts()`: enough to prove the select is built from the payload */
const SCRIPTS = ['LATIN', 'GREEK', 'CYRILLIC', 'ARABIC', 'HAN'];

const LANGUAGE_LABELS = {
	ar: 'Arabic',
	de: 'German',
	el: 'Greek',
	en: 'English',
	es: 'Spanish',
	fr: 'French',
	he: 'Hebrew',
	hi: 'Hindi',
	ja: 'Japanese',
	ko: 'Korean',
	pt: 'Portuguese',
	ru: 'Russian',
	th: 'Thai',
	zh: 'Chinese',
	'und-Arab': 'Arabic script',
	'und-Cyrl': 'Cyrillic script',
	'und-Grek': 'Greek script',
	'und-Hans': 'Han (Simplified)',
	'und-Hebr': 'Hebrew script',
	'und-Zsye': 'Emoji',
};

function languageMap() {
	const groups = [];

	PACK_ENTRIES.filter(
		(entry) => entryRows(entry.source, entry.entry).length > 0
	).forEach((entry) => {
		const codes = (entry.languages || entry.scripts || '')
			.split(',')
			.filter(Boolean);

		if (codes.length === 0) {
			return;
		}

		groups.push({
			group: `${entry.source}/${entry.entry}`,
			label: entry.label,
			rows: codes.map((code) =>
				mapRow(code, entryRows(entry.source, entry.entry)[0].id)
			),
		});
	});

	groups.push({
		group: 'bundled',
		label: 'Bundled · Arimo',
		rows: ['und-Grek', 'und-Cyrl', 'und-Hebr', 'el', 'ru', 'he'].map(
			(code) => mapRow(code, 'gfpdf-arimo')
		),
	});

	const covered = new Set(
		groups.flatMap((group) => group.rows.map((row) => row.code))
	);
	const others = Object.keys(state.overrides).filter(
		(code) => !covered.has(code)
	);

	if (others.length) {
		groups.push({
			group: 'other',
			label: 'Other languages',
			rows: others.map((code) => mapRow(code, '*')),
		});
	}

	return groups;
}

function mapRow(code, defaultFont) {
	return {
		code,
		label: LANGUAGE_LABELS[code] ?? code,
		default_font: defaultFont,
		font: state.overrides[code] ?? defaultFont,
	};
}

/* What `Rest_Font_Sources::get_filter_labels()` publishes, for the ids these fixtures use */
const FILTER_LABELS = {
	'sans-serif': 'Sans-serif',
	serif: 'Serif',
	display: 'Display',
	handwriting: 'Handwriting',
	monospace: 'Monospace',
	latin: 'Latin',
	'latin-ext': 'Latin Extended',
	greek: 'Greek',
	cyrillic: 'Cyrillic',
	vietnamese: 'Vietnamese',
};

function filterLabel(id) {
	return (
		FILTER_LABELS[id] ??
		id
			.split('-')
			.map((part) => part.charAt(0).toUpperCase() + part.slice(1))
			.join(' ')
	);
}

function filtersFor(entries) {
	const count = (pick) =>
		entries.reduce((totals, row) => {
			pick(row).forEach((id) => {
				totals[id] = (totals[id] ?? 0) + 1;
			});

			return totals;
		}, {});

	const toList = (totals) =>
		Object.entries(totals)
			.sort((a, b) => b[1] - a[1])
			.map(([id, total]) => ({
				id,
				label: filterLabel(id),
				count: total,
			}));

	return {
		category: toList(count((row) => (row.category ? [row.category] : []))),
		subsets: toList(
			count((row) => (row.subsets ? row.subsets.split(',') : []))
		),
	};
}

function isStale(synced) {
	if (!synced) {
		return true;
	}

	return Date.now() - Date.parse(synced) > 45 * 86400000;
}

function keyFromLabel(label) {
	return label.toLowerCase().replace(/[^a-z0-9]/g, '');
}

function uniqueKey(key) {
	let candidate = key;
	let suffix = 1;

	while (state.rows.some((row) => row.id === candidate)) {
		candidate = `${key}${++suffix}`;
	}

	return candidate;
}

function rolesToFiles(files) {
	return Object.entries(files).reduce((map, [role, name]) => {
		if (name) {
			map[role] = {
				role,
				variant: null,
				path: name,
				url: null,
				size: 180000,
				missing: 0,
			};
		}

		return map;
	}, {});
}
