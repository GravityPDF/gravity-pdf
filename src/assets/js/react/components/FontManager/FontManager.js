/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
/* Components */
import FontManagerHeader from './FontManagerHeader'
import FontManagerBody from './FontManagerBody'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontManager component
 *
 * @since 6.0
 */
export class FontManager extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    id: PropTypes.string,
    history: PropTypes.object.isRequired
  }

  /**
   * @since 6.0
   */
  constructor (props) {
    super(props)
    this.handleFocus = this.handleFocus.bind(this)
  }

  /**
   * On mount, add focus event to document option on mount
   * Also, if focus isn't currently applied to the search box we'll apply it
   * to our container to help with tabbing between elements
   *
   * @since 6.0
   */
  componentDidMount () {
    document.addEventListener('focus', this.handleFocus, true)

    /* Add focus if not currently applied to search box */
    if (document.activeElement && document.activeElement.className !== 'wp-filter-search') {
      this.container.focus()
    }
  }

  /**
   * Cleanup our document event listeners
   *
   * @since 6.0
   */
  componentWillUnmount () {
    document.removeEventListener('focus', this.handleFocus, true)
  }

  /**
   * When a focus event is fired and it's not apart of any DOM elements in our
   * container we will focus the container instead. In most cases this keeps the focus from
   * jumping outside our font manager container and allows for better keyboard navigation
   *
   * @param e: object
   *
   * @since 6.0
   */
  handleFocus (e) {
    if (!this.container.contains(e.target)) {
      e.stopPropagation()
      this.container.focus()
    }
  }

  /**
   * Display font manager UI
   *
   * @since 6.0
   */
  render () {
    const { id, history } = this.props

    return (
      <div
        data-test='component-FontManager'
        ref={node => (this.container = node)}
        tabIndex='140'
      >
        <div className='backdrop theme-backdrop' />
        <div className='container theme-wrap font-manager'>
          <FontManagerHeader id={id} />

          <FontManagerBody id={id} history={history} />
        </div>
      </div>
    )
  }
}

export default FontManager
