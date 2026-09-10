/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * The Font Manager's whole client state
 *
 * `fonts`, `sources` and `settings` are `null` until their resolver has answered, which is how every component
 * tells "not loaded yet" from "loaded and empty". `statuses` is the one slice the poller writes, and it is
 * replaced wholesale on every read because the route answers with the whole map.
 *
 * @since 7.0
 */
export const DEFAULT_STATE = {
	fonts: null,
	sources: null,
	settings: null,
	statuses: {},
	entries: {},
	searches: {},
	activeFont: '',
	busy: {},
	errors: {},
};

/**
 * @param {Object} state
 * @param {Object} action
 *
 * @return {Object} The next state
 *
 * @since 7.0
 */
export function reducer(state = DEFAULT_STATE, action = {}) {
	switch (action.type) {
		case 'RECEIVE_FONTS':
			return { ...state, fonts: action.fonts };

		case 'RECEIVE_SOURCES':
			return { ...state, sources: action.sources };

		case 'RECEIVE_SETTINGS':
			return { ...state, settings: action.settings };

		case 'RECEIVE_STATUSES':
			/* The poller reads on a timer; an unchanged map must not re-render every card that selects it */
			return same(state.statuses, action.statuses)
				? state
				: { ...state, statuses: action.statuses };

		case 'RECEIVE_ENTRY':
			return {
				...state,
				entries: { ...state.entries, [action.id]: action.entry },
			};

		case 'RECEIVE_SEARCH':
			return {
				...state,
				searches: { ...state.searches, [action.key]: action.results },
			};

		case 'INVALIDATE_SEARCHES':
			return { ...state, searches: {} };

		case 'SET_ACTIVE_FONT':
			return { ...state, activeFont: action.id };

		case 'SET_BUSY':
			return {
				...state,
				busy: { ...state.busy, [action.key]: action.busy },
			};

		case 'SET_ERROR':
			return {
				...state,
				errors: { ...state.errors, [action.key]: action.message },
			};

		default:
			return state;
	}
}

/**
 * Whether two status maps say the same thing
 *
 * One level deep is enough: the status object is flat apart from `update`, which only changes when `version`
 * does, and `version` is compared here.
 *
 * @param {Object} before
 * @param {Object} after
 *
 * @return {boolean} Whether the two are equivalent
 *
 * @since 7.0
 */
function same(before, after) {
	const ids = Object.keys(after);

	if (ids.length !== Object.keys(before).length) {
		return false;
	}

	return ids.every((id) => {
		const a = before[id];
		const b = after[id];

		return (
			!!a &&
			a.phase === b.phase &&
			a.files_done === b.files_done &&
			a.installed === b.installed &&
			a.update_available === b.update_available &&
			a.stuck === b.stuck &&
			a.error === b.error &&
			a.update?.version === b.update?.version
		);
	});
}

export default reducer;
