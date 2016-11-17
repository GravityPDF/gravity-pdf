import React from 'react'

import { render } from 'react-dom'
import { Provider } from 'react-redux'

import Route from 'react-router/lib/Route'
import Router from 'react-router/lib/Router'
import hashHistory from 'react-router/lib/hashHistory'

import TemplateList from '../components/TemplateList'
import TemplateSingle from '../components/TemplateSingle'
import Empty from '../components/Empty'

export const Routes = () => (
  <Router history={hashHistory}>
    <Route path="template" component={TemplateList} noTemplateFoundText={GFPDF.no_templates_found} activateText={GFPDF.activate} />
    <Route path="template/:id" component={TemplateSingle} activateText={GFPDF.activate} />

    <Route path="*" component={Empty}/>
  </Router>)

/* Setup React Router to easily display different components when the Hash URL gets updated */
export default function TemplatesRouter (store) {
  render((<Provider store={store}>
    <Routes />
  </Provider>), document.getElementById('gfpdf-overlay'))
}