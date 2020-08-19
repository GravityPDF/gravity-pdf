import React from 'react'
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
        render={props => <FontManager {...props} />}
      />
      <Route
        exact
        path='/fontmanager/:id'
        render={props => <FontManager {...props} />}
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
