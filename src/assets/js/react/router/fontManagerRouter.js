import React from 'react'
import PropTypes from 'prop-types'
import { render } from 'react-dom'
import { HashRouter as Router, Route, Switch } from 'react-router-dom'
import FontManager from '../components/FontManager/FontManager'
import { Provider } from 'react-redux'
import Empty from '../components/Empty'

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

Routes.propTypes = {
  match: PropTypes.object,
  history: PropTypes.object
}

export default function FontManagerRouter (store) {
  render((
    <Provider store={store}>
      <Routes />
    </Provider>
  ), document.querySelector('#font-manager-overlay'))
}
