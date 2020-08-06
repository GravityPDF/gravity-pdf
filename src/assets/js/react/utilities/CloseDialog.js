import React from 'react'
import PropTypes from 'prop-types'
import { withRouter } from 'react-router-dom'

/**
 * Renders our close dialog element
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * React Component
 *
 * @since 6.0
 */
export class CloseDialog extends React.Component {
  /**
   * @since 6.0
   */
  static propTypes = {
    history: PropTypes.object
  }

  /**
   * Assign keydown listener to document on mount
   *
   * @since 6.0
   */
  componentDidMount () {
    document.addEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Remove keydown listener to document on mount
   *
   * @since 6.0
   */
  componentWillUnmount () {
    document.removeEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Check if Escape key pressed and current event target isn't our search box,
   * or the search box is blank already
   *
   * @param {Object} e Event
   *
   * @since 6.0
   */
  handleKeyPress = e => {
    /* Escape Key */
    if (e.keyCode === 27 && (e.target.className !== 'wp-filter-search' || e.target.value === '')) {
      this.handleCloseDialog()
    }
  }

  /**
   * @since 6.0
   */
  handleCloseDialog = () => {
    /* trigger router */
    this.props.history.push('/')
  }

  /**
   * @since 6.0
   */
  render () {
    return (
      <button
        data-test='component-CloseDialog'
        className='close dashicons dashicons-no'
        tabIndex='142'
        onClick={this.handleCloseDialog}
        onKeyDown={this.handleKeyPress}
        aria-label='close'
      >
        <span className='screen-reader-text'>Close dialog</span>
      </button>
    )
  }
}

export default withRouter(CloseDialog)
