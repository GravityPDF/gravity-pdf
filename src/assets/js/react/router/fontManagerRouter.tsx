/* Dependencies */
import { createRoot } from '@wordpress/element';
import { Routes as Switch, Route } from 'react-router-dom';
/* Components */
import FontManager from '../components/FontManager/FontManager';
import Empty from '../components/Empty';
import withRouterHooks from '../utilities/withRouterHooks';
import CustomHashRouter from '../components/CustomHashRouter';

/**
 * React Router routes for the Font Manager.
 * We are using hashHistory instead of browserHistory so as not to affect the backend.
 *
 * Routes include:
 *
 * /fontmanager (../components/FontManager)
 * /fontmanager/:id (../components/FontManager/UpdateFont)
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
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
 * Setup React Router for the Font Manager — no Provider needed,
 * components read state directly from the @wordpress/data global registry.
 *
 * @since 6.0
 */
export function fontManagerRouter(): void {
	const container = document.querySelector('#font-manager-overlay');

	const root = createRoot(container!);

	root.render(<Routes />);
}
