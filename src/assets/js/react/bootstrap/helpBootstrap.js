import React from 'react'
import { render } from 'react-dom'
import HelpContainer from '../components/Help/HelpContainer'

export default function helpBootstrap() {

  render(
    <HelpContainer />,
    document.getElementById('search-knowledgebase')
  )
}
