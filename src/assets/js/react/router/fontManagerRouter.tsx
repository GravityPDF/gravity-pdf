/* Dependencies */
import { createRoot } from '@wordpress/element';
import { Routes as Switch, Route } from 'react-router-dom';
import { Provider } from 'react-redux';
/* Components */
import FontManager from '../components/FontManager/FontManager';
import Empty from '../components/Empty';
import withRouterHooks from '../utilities/withRouterHooks';
import CustomHashRouter from '../components/CustomHashRouter';
/* Store */
import { getStore } from '../store';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
export const Routes = (): JSX.Element => (
	<CustomHashRouter>
		<Switch>
			<Route path="/fontmanager/" element={<FontManagerWithRouter />} />
			<Route
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
 * @param store
 * @since 6.0
 */
export function fontManagerRouter(store: ReturnType<typeof getStore>): void {
	const container = document.querySelector('#font-manager-overlay');

	const root = createRoot(container!);

	root.render(
		<Provider store={store}>
			<Routes />
		</Provider>
	);
}
