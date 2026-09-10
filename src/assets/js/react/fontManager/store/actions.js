/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import * as api from '../api';

/**
 * Everything that writes
 *
 * Reads are resolvers; these are the actions a click runs. Each one marks its own key busy, re-reads whatever
 * the write invalidated, and turns a `WP_Error` into a snackbar rather than letting it reach a component.
 *
 * @since 7.0
 */

export const receiveFonts = (fonts) => ({ type: 'RECEIVE_FONTS', fonts });
export const receiveSources = (sources) => ({
	type: 'RECEIVE_SOURCES',
	sources,
});
export const receiveSettings = (settings) => ({
	type: 'RECEIVE_SETTINGS',
	settings,
});
export const receiveStatuses = (statuses) => ({
	type: 'RECEIVE_STATUSES',
	statuses,
});
export const receiveEntry = (id, entry) => ({
	type: 'RECEIVE_ENTRY',
	id,
	entry,
});
export const receiveSearch = (key, results) => ({
	type: 'RECEIVE_SEARCH',
	key,
	results,
});
export const setActiveFont = (id) => ({ type: 'SET_ACTIVE_FONT', id });
export const setBusy = (key, busy) => ({ type: 'SET_BUSY', key, busy });
export const setError = (key, message) => ({
	type: 'SET_ERROR',
	key,
	message,
});

/**
 * Re-read the font list, and the statuses that describe it
 *
 * @since 7.0
 */
export const refreshFonts =
	() =>
	async ({ dispatch }) => {
		const [fonts, statuses] = await Promise.all([
			api.fetchFonts(),
			api.fetchStatus(),
		]);

		dispatch(receiveFonts(fonts));
		dispatch(receiveStatuses(statuses));
	};

/**
 * Re-read `GET /fonts/status` alone — the poller's one call
 *
 * @since 7.0
 */
export const refreshStatuses =
	() =>
	async ({ dispatch }) => {
		dispatch(receiveStatuses(await api.fetchStatus()));
	};

/**
 * Install (or update) one catalogue entry
 *
 * @param {string} source
 * @param {string} entry
 * @param {Object} body   Optional `{ label, variants }`
 *
 * @since 7.0
 */
export const installEntry =
	(source, entry, body = {}) =>
	async ({ dispatch }) => {
		const id = `${source}/${entry}`;

		await run(dispatch, id, async () => {
			dispatch(
				receiveStatuses(await api.installEntry(source, entry, body))
			);
			dispatch(refreshStatuses());
		});
	};

/**
 * Remove every row one entry installed
 *
 * @param {string} source
 * @param {string} entry
 *
 * @since 7.0
 */
export const removeEntry =
	(source, entry) =>
	async ({ dispatch, registry }) => {
		const id = `${source}/${entry}`;

		await run(dispatch, id, async () => {
			await api.removeEntry(source, entry);
			await dispatch(refreshFonts());

			notify(registry, __('Font removed', 'gravity-pdf'));
		});
	};

/**
 * Queue every pending update
 *
 * @since 7.0
 */
export const updateAllEntries =
	() =>
	async ({ dispatch }) => {
		await run(dispatch, 'updates', async () => {
			await api.updateAll();
			dispatch(refreshStatuses());
		});
	};

/**
 * Ask the server to refresh every stale catalogue
 *
 * @return {Promise<boolean>} Whether anything was queued
 *
 * @since 7.0
 */
export const refreshSources =
	() =>
	async ({ dispatch, registry }) => {
		let queued = false;

		await run(dispatch, 'sources', async () => {
			const result = await api.syncSources();

			queued = !result.up_to_date;

			dispatch(receiveSources(await api.fetchSources()));

			if (!queued) {
				notify(registry, __('Up to date', 'gravity-pdf'));
			}
		});

		return queued;
	};

/**
 * Re-read the source records, without asking for a sync
 *
 * @since 7.0
 */
export const reloadSources =
	() =>
	async ({ dispatch }) => {
		dispatch(receiveSources(await api.fetchSources()));
	};

/**
 * Drop the browser's cached pages
 *
 * They are keyed by term, filter and page, so browsing a catalogue of 1,800 families retains every page
 * visited. Closing the modal is the natural point to let them go.
 *
 * @since 7.0
 */
export const invalidateSearches = () => ({ type: 'INVALIDATE_SEARCHES' });

/**
 * Create or edit one custom font row
 *
 * @param {?string} id   The row to edit, or null to upload a new one
 * @param {Object}  body The request body
 *
 * @return {Promise<?Object>} The saved row
 *
 * @since 7.0
 */
export const saveFont =
	(id, body) =>
	async ({ dispatch, registry }) => {
		let row = null;

		await run(dispatch, id ?? 'new', async () => {
			row = id
				? await api.editFont(id, body)
				: await api.uploadFont(body);

			await dispatch(refreshFonts());

			notify(registry, __('Saved', 'gravity-pdf'));
		});

		return row;
	};

/**
 * Delete one row and the files no other row records
 *
 * @param {string} id
 *
 * @since 7.0
 */
export const deleteFont =
	(id) =>
	async ({ dispatch, registry }) => {
		await run(dispatch, id, async () => {
			await api.deleteFont(id);
			await dispatch(refreshFonts());

			notify(registry, __('Font deleted', 'gravity-pdf'));
		});
	};

/**
 * Save the language settings panel
 *
 * @param {Object} body The four §4.8 keys
 *
 * @since 7.0
 */
export const saveSettings =
	(body) =>
	async ({ dispatch, registry }) => {
		await run(dispatch, 'settings', async () => {
			dispatch(receiveSettings(await api.saveSettings(body)));

			notify(registry, __('Language settings saved', 'gravity-pdf'));
		});
	};

/**
 * Run one write: busy on, error cleared, error captured, busy off
 *
 * @param {Function} dispatch
 * @param {string}   key
 * @param {Function} write
 *
 * @since 7.0
 */
async function run(dispatch, key, write) {
	dispatch(setBusy(key, true));
	dispatch(setError(key, ''));

	try {
		await write();
	} catch (failure) {
		dispatch(
			setError(
				key,
				failure?.message ??
					__('Something went wrong. Try again.', 'gravity-pdf')
			)
		);
	} finally {
		dispatch(setBusy(key, false));
	}
}

/**
 * A snackbar, through the notices store every WordPress screen already has
 *
 * @param {Object} registry The data registry a thunk is handed
 * @param {string} message
 *
 * @since 7.0
 */
export function notify(registry, message) {
	registry
		.dispatch(noticesStore)
		.createSuccessNotice(message, { type: 'snackbar' });
}
