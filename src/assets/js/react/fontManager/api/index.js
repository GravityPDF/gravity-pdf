/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { API_ROOT } from '../constants';
import { mockMiddleware, mockOnly } from './mock';

/**
 * Every request the Font Manager makes
 *
 * One module so the fixtures have a single seam: nothing else in the UI knows which routes the site answers and
 * which it does not. Every function below speaks to the real path either way.
 *
 * @since 7.0
 */

/**
 * The `/fonts` routes the plugin does not serve yet
 *
 * `/fonts/settings` is Phase 6 (the language settings panel), so the Language tab reads fixtures while the rest of
 * the manager talks to the site. When that route lands this array empties and the whole of `./mock` goes with it.
 *
 * @since 7.0
 */
const PENDING_ROUTES = ['/fonts/settings'];

let registered = false;

/**
 * Serve the routes the site cannot answer yet from the in-browser fixtures
 *
 * @since 7.0
 */
export function enablePendingRoutes() {
	register(mockOnly(PENDING_ROUTES));
}

/**
 * Serve every `/fonts` route from the fixtures
 *
 * The Jest suite's seam: a component test standing up REST fixtures would be testing WordPress.
 *
 * @since 7.0
 */
export function enableMockApi() {
	register(mockMiddleware);
}

function register(middleware) {
	if (registered) {
		return;
	}

	apiFetch.use(middleware);
	registered = true;
}

const get = (path) => apiFetch({ path: API_ROOT + path });
const send = (method) => (path, data) =>
	apiFetch({ path: API_ROOT + path, method, data });

const post = send('POST');
const remove = send('DELETE');

export const fetchFonts = () => get('/');
export const fetchSources = () => get('/sources');
export const fetchStatus = () => get('/status');
export const fetchSettings = () => get('/settings');

export const fetchSourceEntries = (source, query) =>
	get(addQueryArgs(`/sources/${source}`, query));

export const fetchSourceEntry = (source, entry) =>
	get(`/sources/${source}/${entry}`);

export const syncSources = () => post('/sources/sync');

export const installEntry = (source, entry, body = {}) =>
	post(`/sources/${source}/${entry}`, body);

export const removeEntry = (source, entry) =>
	remove(`/sources/${source}/${entry}`);

export const updateAll = () => post('/updates');

export const saveSettings = (body) => post('/settings', body);

export const uploadFont = (body) => post('/', body);
export const editFont = (id, body) => post(`/${id}`, body);
export const deleteFont = (id) => remove(`/${id}`);
