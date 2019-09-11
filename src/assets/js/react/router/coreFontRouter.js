import React from 'react'
import PropTypes from 'prop-types'
import { HashRouter as Router, Route, Switch } from 'react-router-dom'
import CoreFontContainer from '../components/CoreFonts/CoreFontContainer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
 * @param button DOM Node containing the original static <button> markup (gets replaced by React)
 *
 * @since 5.0
 */
const Routes = ({ button }) => (
  <Router>
    <Switch>
      <Route render={(props) => <CoreFont history={props.history} button={button} />} />

      <Route
        path='/downloadCoreFonts'
        exact
        render={(props) => <CoreFont history={props.history} button={button} />} />

      <Route
        path='/retryDownloadCoreFonts'
        exact
        render={(props) => <CoreFont history={props.history} button={button} />} />
    </Switch>
  </Router>
)

/**
 * @since 5.0
 */
Routes.propTypes = {
  history: PropTypes.object,
  button: PropTypes.object
}

/**
 * Because we used the same component multiple times above, the real component was abstracted
 *
 * @param history HashHistory object
 * @param button DOM Node
 *
 * @since 5.0
 */
const CoreFont = ({ history, button }) => (
  <CoreFontContainer
    history={history}
    location={history.location}
    buttonClassName={button.className}
    buttonText={button.innerText}
    listUrl={GFPDF.coreFontListUrl}
    success={GFPDF.coreFontSuccess}
    error={GFPDF.coreFontError}
    githubError={GFPDF.coreFontGithubError}
    itemPending={GFPDF.coreFontItemPendingMessage}
    itemSuccess={GFPDF.coreFontItemSuccessMessage}
    itemError={GFPDF.coreFontItemErrorMessage}
    counterText={GFPDF.coreFontCounter}
    retryText={GFPDF.coreFontRetry}
  />
)

/**
 * @since 5.0
 */
CoreFont.propTypes = {
  history: PropTypes.object,
  button: PropTypes.object
}

export default Routes
