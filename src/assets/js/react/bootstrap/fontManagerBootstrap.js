/* Dependencies */
import React, { lazy, Suspense } from 'react'
import { render } from 'react-dom'
import { HashRouter as Router, Route } from 'react-router-dom'
/* Routes */
import { fontManagerRouter } from '../router/fontManagerRouter'
/* Redux store */
import { getStore } from '../store'
/* Components */
const AdvancedButton = lazy(() => import('../components/FontManager/AdvancedButton'))

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Mount our font manager advanced button on the DOM
 *
 * @param defaultFontField: div element
 * @param buttonStyle: string
 *
 * @since 6.0
 */
export function fontManagerBootstrap (defaultFontField, buttonStyle) {
  const store = getStore()
  /* Prevent button reset styling on tools tab */
  const preventButtonReset = !buttonStyle ? '' : buttonStyle

  createAdvancedButtonWrapper(defaultFontField, preventButtonReset)

  render(
    <Suspense fallback={<div>{GFPDF.spinnerAlt}</div>}>
      <Router>
        <Route render={props => <AdvancedButton {...props} store={store} />} />
      </Router>
    </Suspense>,
    document.querySelector('#gpdf-advance-font-manager-selector' + preventButtonReset)
  )

  fontManagerRouter(store)
}

/**
 * Create html element wrapper for our font manager advanced button
 *
 * @param defaultFontField: div element
 * @param preventButtonReset: string
 *
 * @since 6.0
 */
export function createAdvancedButtonWrapper (defaultFontField, preventButtonReset) {
  const fontWrapper = document.createElement('span')
  fontWrapper.setAttribute('id', 'gpdf-advance-font-manager-selector' + preventButtonReset)

  const popupWrapper = document.createElement('div')
  popupWrapper.setAttribute('id', 'font-manager-overlay')
  popupWrapper.setAttribute('class', 'theme-overlay')

  if (defaultFontField.nodeName === 'SELECT') {
    const wrapper = document.createElement('div')
    wrapper.setAttribute('id', 'gfpdf-settings-field-wrapper-font-container')
    wrapper.innerHTML = defaultFontField.outerHTML
    wrapper.appendChild(fontWrapper)
    wrapper.appendChild(popupWrapper)
    defaultFontField.outerHTML = wrapper.outerHTML
  } else {
    defaultFontField.appendChild(fontWrapper)
    defaultFontField.appendChild(popupWrapper)
  }
}
