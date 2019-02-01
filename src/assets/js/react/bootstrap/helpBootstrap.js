import React from 'react'
import { render } from 'react-dom'
import { Provider } from 'react-redux'
import { getStore } from '../store'
import HelpContainer from '../components/Help/HelpContainer'

export default function helpBootstrap() {
  const store = getStore()

  render(
    <Provider store={store}>
      <HelpContainer />
    </Provider>,
    document.getElementById('search-knowledgebase')
  )
}
