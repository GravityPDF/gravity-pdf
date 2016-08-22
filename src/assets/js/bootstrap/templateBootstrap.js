import React from 'react'
import { render } from 'react-dom'

import { createStore, combineReducers } from 'redux'
import watch from 'redux-watch'

import templateRouter from '../router/templateRouter'
import templateReducer from '../reducers/templateReducer'
import TemplateButton from '../components/TemplateButton'

export default function templateBootstrap ($templateField) {

  /* Combine our Redux Reducers */
  const reducers = setupReducers()

  /* Create our store and enable the Redux dev tools, if they exist */
  const store = createStore(reducers, window.devToolsExtension && window.devToolsExtension())

  /* Create our button container and render our component in it */
  createTemplateMarkup($templateField)

  render(
    <TemplateButton store={store} buttonText={GFPDF.advanced_templates}/>,
    document.getElementById('gpdf-advance-template-selector')
  )

  /* Mount our router */
  templateRouter(store)

  /* Listen for Redux store updates to the template.activeTemplate item and
   * update our $templateField value
   */
  activeTemplateStoreListener(store, $templateField)
}

export function setupReducers () {
  return combineReducers({
    template: templateReducer,
  })
}

export function createTemplateMarkup ($templateField) {
  $templateField
    .next()
    .after('<span id="gpdf-advance-template-selector">')
    .next()
    .after('<div id="gfpdf-overlay" class="theme-overlay">')
}

export function activeTemplateStoreListener (store, $templateField) {
  let w = watch(store.getState, 'template.activeTemplate')
  store.subscribe(w((template) => {
    $templateField
      .val(template)
      .trigger('chosen:updated')
      .trigger('change')
  }))
}