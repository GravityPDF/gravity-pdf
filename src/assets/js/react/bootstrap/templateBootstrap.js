import React from 'react'
import { render } from 'react-dom'
import { HashRouter as Router, Route } from 'react-router-dom'
import request from 'superagent'

import { createStore, combineReducers } from 'redux'
import watch from 'redux-watch'

import { selectTemplate } from '../actions/templates'
import templateRouter from '../router/templateRouter'
import templateReducer from '../reducers/templateReducer'
import TemplateButton from '../components/TemplateButton'

/**
 * Advanced Template Selector Bootstrap
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
 Found
 */

/**
 * Handles the loading of our Fancy Template Selector
 *
 * @param {Object} $templateField The jQuery select box we should attach the fancy template selector to
 *
 * @since 4.1
 */
export default function templateBootstrap ($templateField) {

  /* Combine our Redux Reducers */
  const reducers = setupReducers()

  /* Create our store and enable the Redux dev tools, if they exist */
  const store = createStore(reducers, window.devToolsExtension && window.devToolsExtension())

  /* Create our button container and render our component in it */
  createTemplateMarkup($templateField)

  /* Render our React Component in the DOM */
  render(
    <Router>
      <Route render={(props) => <TemplateButton {...props} store={store} buttonText={GFPDF.advanced}/>} />
    </Router>,
    document.getElementById('gpdf-advance-template-selector')
  )

  /* Mount our router */
  templateRouter(store)

  /*
   * Listen for Redux store updates and do DOM updates
   */
  activeTemplateStoreListener(store, $templateField)
  templateChangeStoreListener(store, $templateField)
}

/**
 * Combine our Redux reducers for use in a single store
 * If you want to add new top-level keys to our store, this is the place
 *
 * @returns {Function}
 *
 * @since 4.1
 */
export function setupReducers () {
  return combineReducers({
    template: templateReducer,
  })
}

/**
 * Dynamically add the required markup to attach our React components to.
 *
 * @param {Object} $templateField The jQuery select box we should attach the fancy template selector to
 *
 * @since 4.1
 */
export function createTemplateMarkup ($templateField) {
  $templateField
    .next()
    .after('<span id="gpdf-advance-template-selector">')
    .next()
    .after('<div id="gfpdf-overlay" class="theme-overlay">')
}

/**
 * Listen for updates to the template.activeTemplate data in our Redux store
 * and update the select box value based on this change. Also, listen for changes
 * to our select box and update the store when needed.
 *
 * @param {Object} store The Redux store returned from createStore()
 * @param {Object} $templateField The jQuery select box we should attach the fancy template selector to
 *
 * @since 4.1
 */
export function activeTemplateStoreListener (store, $templateField) {

  /* Watch our store for changes */
  let w = watch(store.getState, 'template.activeTemplate')
  store.subscribe(w((template) => {

    /* Check store and DOM are different to prevent any update recursions */
    if ($templateField.val() !== template) {
      $templateField
        .val(template)
        .trigger('chosen:updated')
        .trigger('change')
    }
  }))

  /* Watch our DOM for changes */
  $templateField.change(function () {
    /* Check store and DOM are different to prevent any update recursions */
    if (this.value !== store.getState().template.activeTemplate) {
      store.dispatch(selectTemplate(this.value))
    }
  })
}

/**
 * PHP builds the Select box DOM for the templates and when we add or delete a template we need to rebuild this.
 * Instead of duplicating the code on both server and client side we do an AJAX call to get the new Selex box HTML when
 * the template.list length changes and update the DOM accordingly.
 *
 * @param {Object} store The Redux store returned from createStore()
 * @param {Object} $templateField The jQuery select box we should attach the fancy template selector to
 *
 * @since 4.1
 */
export function templateChangeStoreListener (store, $templateField) {

  /* Track the initial list size */
  let listCount = store.getState().template.list.size

  /* Watch our store for changes */
  let w = watch(store.getState, 'template.list')
  store.subscribe(w((list) => {

    /* Only update if the list size differs from what we expect */
    if (listCount !== list.size) {
      /* update the list size so we don't run it twice */
      listCount = list.size

      /* Do our AJAX call to get the new Select Box DOM */
      request
        .post(GFPDF.ajaxUrl)
        .field('action', 'gfpdf_get_template_options')
        .field('nonce', GFPDF.ajaxNonce)
        .then((response) => {
          $templateField
            .html(response.text)
            .trigger('chosen:updated')
            .trigger('change')
        })
    }
  }))
}