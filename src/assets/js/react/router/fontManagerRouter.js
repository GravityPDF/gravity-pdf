import React from 'react'
import { render } from 'react-dom'
import { HashRouter as Router, Route, Switch } from 'react-router-dom'
import FontManagerContainer from '../components/FontManager/FontManagerContainer'
import { Provider } from 'react-redux'
import Empty from '../components/Empty'

export const Routes = () => (
  <Router>
    <Switch>
      <Route
        path='/fontmanager'
        exact
        render={props => <FontManagerContainer {...props} />}
      />
      <Route component={Empty} />
    </Switch>
  </Router>
)

export default function FontManagerRouter (store) {
  render((
    <Provider store={store}>
      <Routes />
    </Provider>
  ), document.querySelector('#font-manager-overlay'))
}
