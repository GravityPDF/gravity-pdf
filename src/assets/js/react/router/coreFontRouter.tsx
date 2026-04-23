/* Dependencies */
import { Routes as Switch, Route, HashRouter } from 'react-router';
/* Components */
import CoreFontContainer from '../components/CoreFonts/CoreFontContainer';
import Empty from '../components/Empty';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Contains the React Router Routes for our Core Font downloader.
 * We are using hashHistory instead of browserHistory so as not to affect the backend
 *
 * Routes include:
 *
 * /downloadCoreFonts
 * /retryDownloadCoreFonts
 *
 * @since 5.0
 */
const Routes = (): JSX.Element => {
	return (
		<HashRouter>
			<Switch>
				<Route path="/" element={<CoreFontContainer />} />
				<Route
					path="/downloadCoreFonts"
					element={<CoreFontContainer />}
				/>
				<Route
					path="/retryDownloadCoreFonts"
					element={<CoreFontContainer />}
				/>
				<Route path="*" element={<Empty />} />
			</Switch>
		</HashRouter>
	);
};

export default Routes;
