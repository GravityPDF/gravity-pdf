/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { Routes as Switch, Route } from 'react-router-dom';
/* Components */
import CoreFontContainer from '../components/CoreFonts/CoreFontContainer';
import Empty from '../components/Empty';
/* Helpers */
import withRouterHooks from '../utilities/withRouterHooks.js';
import CustomHashRouter from '../components/CustomHashRouter';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
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
 * @param { HTMLButtonElement } button DOM Node containing the original static <button> markup (gets replaced by React)
 *
 * @since 5.0
 */
const Routes = ({ button }) => {
	return (
		<CustomHashRouter>
			<Switch>
				<Route path="/" element={<CoreFont button={button} />} />

				<Route
					path="/downloadCoreFonts"
					exact
					element={<CoreFont button={button} />}
				/>

				<Route
					path="/retryDownloadCoreFonts"
					exact
					element={<CoreFont button={button} />}
				/>
				<Route path="*" element={<Empty />} />
			</Switch>
		</CustomHashRouter>
	);
};

/**
 * @since 5.0
 */
Routes.propTypes = {
	button: PropTypes.object,
};

/**
 * Because we used the same component multiple times above, the real component was abstracted
 *
 * @param { HTMLButtonElement } button DOM Node
 *
 * @since 5.0
 */
const CoreFont = ({ button }) => {
	return (
		<CoreFontContainerWithRouter
			buttonClassName={button.className}
			buttonText={button.innerText}
			success={GFPDF.coreFontSuccess}
			error={GFPDF.coreFontError}
			itemPending={GFPDF.coreFontItemPendingMessage}
			itemSuccess={GFPDF.coreFontItemSuccessMessage}
			itemError={GFPDF.coreFontItemErrorMessage}
			counterText={GFPDF.coreFontCounter}
			retryText={GFPDF.coreFontRetry}
		/>
	);
};

const CoreFontContainerWithRouter = withRouterHooks(CoreFontContainer);

/**
 * @since 5.0
 */
CoreFont.propTypes = {
	button: PropTypes.object,
};

export default Routes;
