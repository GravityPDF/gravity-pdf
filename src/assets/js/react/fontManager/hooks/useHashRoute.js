/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * The Font Manager's routes, in the order they are matched
 *
 * The literal segments are declared before `/fontmanager/:id`, mirroring the REST registration order: a font
 * key can never equal a route word (§4.7 reserves them), but the router only stays true to that while the
 * literals are tried first. `browse` is one pair of routes for every source — no source id appears here.
 *
 * @since 7.0
 */
export const ROUTES = [
	['home', /^\/fontmanager\/?$/, []],
	['settings', /^\/fontmanager\/settings\/?$/, []],
	['updates', /^\/fontmanager\/updates\/?$/, []],
	['browse', /^\/fontmanager\/browse\/?$/, []],
	['browse', /^\/fontmanager\/browse\/([a-z0-9-]+)\/?$/, ['source']],
	[
		'entry',
		/^\/fontmanager\/browse\/([a-z0-9-]+)\/([a-z0-9-]+)\/?$/,
		['source', 'entry'],
	],
	['font', /^\/fontmanager\/([a-z0-9_-]+)\/?$/, ['id']],
];

/**
 * Match one path against the table
 *
 * @param {string} path
 *
 * @return {?Object} `{ name, params }`, or null when the path is not the Font Manager's
 *
 * @since 7.0
 */
export function matchRoute(path) {
	for (const [name, pattern, names] of ROUTES) {
		const match = path.match(pattern);

		if (match) {
			return {
				name,
				params: names.reduce((params, key, index) => {
					params[key] = match[index + 1];

					return params;
				}, {}),
			};
		}
	}

	return null;
}

const currentPath = () => window.location.hash.replace(/^#/, '');

/**
 * The route the address bar is on, and the two ways to change it
 *
 * Hash routing, as the 6.x Font Manager used, so nothing the modal does touches the page WordPress rendered.
 *
 * @return {Object} `{ route, navigate, close }`
 *
 * @since 7.0
 */
export function useHashRoute() {
	const [path, setPath] = useState(currentPath);

	useEffect(() => {
		const onChange = () => setPath(currentPath());

		window.addEventListener('hashchange', onChange);

		return () => window.removeEventListener('hashchange', onChange);
	}, []);

	const navigate = useCallback((to) => {
		window.location.hash = to;
	}, []);

	const close = useCallback(() => {
		/* `pushState` rather than clearing the hash, so the browser does not scroll to the top of the page */
		window.history.pushState(
			'',
			document.title,
			window.location.pathname + window.location.search
		);

		setPath('');
	}, []);

	return { route: matchRoute(path), navigate, close };
}
