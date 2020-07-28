import React from 'react'
import { render } from 'react-dom'
import { HashRouter as Router, Route } from 'react-router-dom'
import { getStore } from '../store'
import fontManagerRouter from '../router/fontManagerRouter'
import AdvancedButton from '../components/FontManager/AdvancedButton'

export function fontManagerBootstrap (defaultFontField) {
  const store = getStore()

  createAdvancedButton(defaultFontField)

  render(
    <Router>
      <Route render={props => <AdvancedButton {...props} store={store} />} />
    </Router>,
    document.querySelector('#gpdf-advance-font-manager-selector')
  )

  fontManagerRouter(store)
}

export function createAdvancedButton (defaultFontField) {
  const wrapper = document.createElement('span')
  wrapper.setAttribute('id', 'gpdf-advance-font-manager-selector')

  const popupWrapper = document.createElement('div')
  popupWrapper.setAttribute('id', 'font-manager-overlay')
  popupWrapper.setAttribute('class', 'theme-overlay')

  defaultFontField.appendChild(wrapper)
  defaultFontField.appendChild(popupWrapper)
}
