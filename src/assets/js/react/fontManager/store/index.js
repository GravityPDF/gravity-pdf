/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { createReduxStore, register } from '@wordpress/data';
import { STORE_NAME } from '../constants';
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';

/**
 * The `gravity-pdf/fonts` store
 *
 * @since 7.0
 */
export const store = createReduxStore(STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
});

let registered = false;

/**
 * Register the store once, however many anchors mounted the Font Manager
 *
 * @return {Object} The store descriptor
 *
 * @since 7.0
 */
export function registerFontStore() {
	if (!registered) {
		register(store);
		registered = true;
	}

	return store;
}

export { STORE_NAME };
