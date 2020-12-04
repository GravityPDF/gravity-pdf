/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { render } from 'react-dom'
import { HashRouter as Router, Route, Switch } from 'react-router-dom'
import { Provider } from 'react-redux'
/* Components */
import FontManager from '../components/FontManager/FontManager'
import Empty from '../components/Empty'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
  <Router>
    <Switch>
      <Route
        exact
        path='/fontmanager/'
        render={props => <FontManager id={props.match.params.id} history={props.history} />}
      />
      <Route
        exact
        path='/fontmanager/:id'
        render={props => <FontManager id={props.match.params.id} history={props.history} />}
      />
      <Route component={Empty} />
    </Switch>
  </Router>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
Routes.propTypes = {
  match: PropTypes.object,
  history: PropTypes.object
}

/**
 * Setup react router with our redux store
 *
 * @param store: object
 *
 * @since 6.0
 */
export default function FontManagerRouter (store) {
  render((
    <Provider store={store}>
      <Routes />
    </Provider>
  ), document.querySelector('#font-manager-overlay'))
}
