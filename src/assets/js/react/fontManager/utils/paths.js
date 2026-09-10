/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * Every route the Font Manager can be sent to
 *
 * The table in `useHashRoute` reads a path; this writes one. Both directions in one module, so a route that
 * moves moves once — and so no caller has to remember that `browse` takes one segment or two.
 *
 * @since 7.0
 */
export const paths = {
	home: () => '/fontmanager/',
	settings: () => '/fontmanager/settings',
	updates: () => '/fontmanager/updates',
	browse: (source = '') =>
		source ? `/fontmanager/browse/${source}` : '/fontmanager/browse',
	entry: (source, entry) => `/fontmanager/browse/${source}/${entry}`,
	font: (id) => `/fontmanager/${id}`,
};

/**
 * Which sidebar row the current route has selected
 *
 * The sidebar asked the route object about its `name` and `params` to work this out, which made every row a
 * second place that knew the route table's shape.
 *
 * @param {?Object} route The matched route
 *
 * @return {Object} `{ id, entry }` — the font key and the `source/entry` the detail pane is showing
 *
 * @since 7.0
 */
export function selectionFrom(route) {
	if (route?.name === 'font') {
		return { id: route.params.id, entry: '' };
	}

	if (route?.name === 'entry') {
		return {
			id: '',
			entry: `${route.params.source}/${route.params.entry}`,
		};
	}

	return { id: '', entry: '' };
}
