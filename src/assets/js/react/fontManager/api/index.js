/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { API_ROOT } from '../constants';
import { mockMiddleware } from './mock';

/**
 * Every request the Font Manager makes
 *
 * One module so the mocked routes have a single seam: `enableMockApi()` is called once at mount and nothing else
 * in the UI knows the difference. When the REST controllers land, that call and `./mock` go, and every function
 * below already speaks to the real route.
 *
 * @since 7.0
 */

let mocked = false;

/**
 * Serve the `/fonts` routes from the in-browser fixtures
 *
 * @since 7.0
 */
export function enableMockApi() {
	if (mocked) {
		return;
	}

	apiFetch.use(mockMiddleware);
	mocked = true;
}

/**
 * Whether the font list came from fixtures rather than from the site
 *
 * @return {boolean} Whether the mocked routes are in place
 *
 * @since 7.0
 */
export function isMocked() {
	return mocked;
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
