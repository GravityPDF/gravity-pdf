/* Dependencies */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { Routes as Switch, Route } from 'react-router-dom';
import { Provider } from 'react-redux';
/* Components */
import FontManager from '../components/FontManager/FontManager';
import Empty from '../components/Empty';
import withRouterHooks from '../utilities/withRouterHooks.js';
import CustomHashRouter from '../components/CustomHashRouter';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Contains the react router routes for our font manager
 * We are using hashHistory instead of browserHistory so as not to affect the backend
 *
 * Routes include:
 *
 * /fontmanager (../components/FontManager)
 * /fontmanager/:id (../components/FontManager/UpdateFont)
 *
 * Button DOM node containing the original static <button> markup (gets replaced by React)
 *
 * @since 6.0
 */
export const Routes = () => (
	<CustomHashRouter>
		<Switch>
			<Route
				exact
				path="/fontmanager/"
				element={<FontManagerWithRouter />}
			/>
			<Route
				exact
				path="/fontmanager/:id"
				element={<FontManagerWithRouter />}
			/>
			<Route path="*" element={<Empty />} />
		</Switch>
	</CustomHashRouter>
);

const FontManagerWithRouter = withRouterHooks(FontManager);

/**
 * Setup react router with our redux store
 *
 * @param { Object } store
 *
 * @since 6.0
 */
export function fontManagerRouter(store) {
	const container = document.querySelector('#font-manager-overlay');

	const root = createRoot(container);

	root.render(
		<Provider store={store}>
			<Routes />
		</Provider>
	);
}
