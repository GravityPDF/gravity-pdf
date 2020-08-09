import React, { Component } from 'react'
import PropTypes from 'prop-types'
import FontManagerHeader from './FontManagerHeader'
import FontManagerBody from './FontManagerBody'

export class FontManager extends Component {
  componentDidMount () {
    document.addEventListener('focus', this.handleFocus, true)

    /* Add focus if not currently applied to search box */
    if (document.activeElement && document.activeElement.className !== 'wp-filter-search') {
      this.container.focus()
    }
  }

  componentWillUnmount () {
    document.removeEventListener('focus', this.handleFocus, true)
  }

  handleFocus = e => {
    if (!this.container.contains(e.target)) {
      e.stopPropagation()
      this.container.focus()
    }
  }

  render () {
    const { id, history } = this.props

    return (
      <div ref={node => (this.container = node)} tabIndex='140'>
        <div className='backdrop theme-backdrop' />
        <div className='container theme-wrap font-manager'>
          <FontManagerHeader id={id} />

          <FontManagerBody id={id} history={history} />
        </div>
      </div>
    )
  }
}

FontManager.propTypes = {
  id: PropTypes.string,
  history: PropTypes.object.isRequired
}

export default FontManager
