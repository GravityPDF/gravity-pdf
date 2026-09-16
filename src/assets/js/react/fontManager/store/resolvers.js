/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import * as api from '../api';
import {
	receiveEntry,
	receiveSearch,
	receiveSettings,
	refreshFonts,
	reloadSources,
} from './actions';
import { searchKey } from '../utils/searchKey';

/**
 * The reads, resolved on first use
 *
 * Only the selectors that *are* a request carry one — the rest of `selectors.js` derives from these three
 * slices through registry selectors, so asking for a derived view resolves the request behind it. Reading a
 * font list is a thing the store already knows how to do, so these are the write actions, not copies of them.
 *
 * @since 7.0
 */

export const getFonts = refreshFonts;
export const getSources = reloadSources;

export const getSettings =
	() =>
	async ({ dispatch }) => {
		dispatch(receiveSettings(await api.fetchSettings()));
	};

export const getEntry =
	(source, entry) =>
	async ({ dispatch }) => {
		const id = `${source}/${entry}`;

		try {
			dispatch(
				receiveEntry(id, await api.fetchSourceEntry(source, entry))
			);
		} catch (failure) {
			/* `false` rather than `null`: the detail pane has to tell "gone" from "still loading" */
			dispatch(receiveEntry(id, false));
		}
	};

export const getSourceEntries =
	(source, query) =>
	async ({ dispatch }) => {
		dispatch(
			receiveSearch(
				searchKey(source, query),
				await api.fetchSourceEntries(source, query)
			)
		);
	};
