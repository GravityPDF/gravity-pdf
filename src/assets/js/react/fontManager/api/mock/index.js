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
import { PER_PAGE } from '../../constants';

/**
 * A stand-in for the `/fonts` routes, until Phase 4's REST work lands
 *
 * Registered as an `apiFetch` middleware, so every component and resolver calls the real path it will keep
 * calling afterwards and this file is the only thing that gets deleted. Requests to anything else fall
 * straight through.
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
				resolve(run());
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

function uploadFont({ data }) {
	const key = uniqueKey(keyFromLabel(data.label ?? 'New font'));

	const row = {
		id: key,
		label: data.label ?? 'New font',
		source: 'custom',
		entry: null,
		coverage: 0,
		version: null,
		enabled: true,
		files: rolesToFiles(data.files ?? {}),
	};

	state.rows.push(row);

	return row;
}

function editFont({ params, data }) {
	const row = state.rows.find((item) => item.id === params[0]);

	if (!row) {
		throw error('invalid_font_id', 'The font could not be found', 400);
	}

	if (data.label !== undefined) {
		row.label = data.label;
	}

	if (data.enabled !== undefined) {
		row.enabled = !!data.enabled;
	}

	if (data.files) {
		row.files = { ...row.files, ...rolesToFiles(data.files) };
	}

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
			coverage: record.coverage,
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
			.map(([id, total]) => ({ id, label: id, count: total }));

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
