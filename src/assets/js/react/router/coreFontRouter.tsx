/* Dependencies */
import { __ } from '@wordpress/i18n';
import { Routes as Switch, Route } from 'react-router';
/* Components */
import CoreFontContainer from '../components/CoreFonts/CoreFontContainer';
import Empty from '../components/Empty';
/* Helpers */
import { HashRouter } from 'react-router';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

interface RoutesProps {
	button: HTMLButtonElement;
}

/**
 * Contains the React Router Routes for our Core Font downloader.
 * We are using hashHistory instead of browserHistory so as not to affect the backend
 *
 * Routes include:
 *
 * /downloadCoreFonts
 * /retryDownloadCoreFonts
 *
 * @param root0
 * @param root0.button
 * @since 5.0
 */
const Routes = ({ button }: RoutesProps): JSX.Element => {
	return (
		<HashRouter>
			<Switch>
				<Route path="/" element={<CoreFont button={button} />} />

				<Route
					path="/downloadCoreFonts"
					element={<CoreFont button={button} />}
				/>

				<Route
					path="/retryDownloadCoreFonts"
					element={<CoreFont button={button} />}
				/>
				<Route path="*" element={<Empty />} />
			</Switch>
		</HashRouter>
	);
};

/**
 * Because we used the same component multiple times above, the real component was abstracted
 *
 * @param root0
 * @param root0.button
 * @since 5.0
 */
const CoreFont = ({ button }: RoutesProps): JSX.Element => {
	return (
		<CoreFontContainer
			buttonClassName={button.className}
			buttonText={button.innerText}
			counterText={__('Fonts remaining:', 'gravity-pdf')}
			retryText={__('Retry Failed Downloads?', 'gravity-pdf')}
		/>
	);
};

export default Routes;
