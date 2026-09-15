/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { API_ROOT, ROLES } from '../constants';

/**
 * Every request the Font Manager makes
 *
 * One module so the Jest fixtures have a single seam: no component knows whether it is talking to the site or to
 * the mocked server, because every function below names the real path either way. The fixtures themselves live
 * in the test tree — a production module importing them shipped them to every admin.
 *
 * @since 7.0
 */

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

export const uploadFont = (body) => writeFont('/', body);
export const editFont = (id, body) => writeFont(`/${id}`, body);
export const deleteFont = (id) => remove(`/${id}`);

/**
 * Hand one offline package to `POST /fonts/import`
 *
 * `body` rather than `data`: `apiFetch`'s `data` shorthand JSON-encodes what it is given and sets a JSON
 * content type, which is neither of the things a 6 MB zip needs. A `FormData` body goes through untouched and
 * lets the browser set the multipart boundary, so the archive arrives as an ordinary `$_FILES` entry — which is
 * what the route reads (`file`), and what a host's own upload cap applies to.
 *
 * @param {File} file The archive the admin chose
 *
 * @return {Promise<Object>} The status map the install was queued under
 *
 * @since 7.0
 */
export const importPackage = (file) => {
	const body = new window.FormData();

	body.append('file', file);

	return apiFetch({ path: `${API_ROOT}/import`, method: 'POST', body });
};

/**
 * Create or edit one custom font, as multipart when the write carries faces and as JSON when it does not
 *
 * `POST /fonts/` and `POST /fonts/{id}` read their `.ttf`s out of `$_FILES` — so a face has to travel as a real
 * multipart part, which `apiFetch`'s `data` shorthand cannot do: it JSON-encodes what it is handed, and a `File`
 * encodes to `{}`. The route then sees no files at all.
 *
 * Emptiness decides it, not the presence of the key: the panel sends `faces` on every save and it is empty for
 * a rename, which has nothing to put in a multipart part. Everything else — a display row's `variants`,
 * multisite's `enabled` toggle — keeps the JSON path, where an object and a boolean survive as themselves
 * rather than arriving as `"[object Object]"` and `"true"`.
 *
 * @param {string}  path
 * @param {Object}  body       `{ label?, enabled?, variants?, faces? }`
 * @param {?Object} body.faces Role → a `File` to write there, or null to clear it
 *
 * @return {Promise<Object>} The saved row
 *
 * @since 7.0
 */
function writeFont(path, { faces, ...rest }) {
	if (!faces || Object.keys(faces).length === 0) {
		return post(path, rest);
	}

	return apiFetch({
		path: API_ROOT + path,
		method: 'POST',
		body: facesForm(rest, faces),
	});
}

/**
 * One custom-font write as `FormData`, under the names the route reads
 *
 * A `File` against a face replaces it; `null` clears it, which the route spells as the face's name present in
 * the body with an empty value (`Rest_Custom_Fonts::update_item()`). A face the admin did not touch is absent
 * from both, and keeps whatever file it has.
 *
 * @param {Object} fields The rest of the write, which past `writeFont()` is `label` alone
 * @param {Object} faces  Role → a `File` to write there, or null to clear it
 *
 * @return {FormData} The request body
 *
 * @since 7.0
 */
function facesForm(fields, faces) {
	const form = new window.FormData();

	Object.entries(fields).forEach(([key, value]) => form.append(key, value));

	ROLES.filter((role) => role.id in faces).forEach((role) =>
		form.append(role.field, faces[role.id] ?? '')
	);

	return form;
}
