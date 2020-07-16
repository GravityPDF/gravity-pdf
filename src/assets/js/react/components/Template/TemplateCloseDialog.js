import React from 'react'
import PropTypes from 'prop-types'
import { withRouter } from 'react-router-dom'

/**
 * Renders our close dialog element
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateCloseDialog extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    history: PropTypes.object,
    closeRoute: PropTypes.string
  }

  /**
   * Assign keydown listener to document on mount
   *
   * @since 4.1
   */
  componentDidMount () {
    document.addEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Remove keydown listener to document on mount
   *
   * @since 4.1
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
   * @since 4.1
   */
  handleKeyPress = e => {
    /* Escape Key */
    if (e.keyCode === 27 && (e.target.className !== 'wp-filter-search' || e.target.value === '')) {
      this.handleCloseDialog()
    }
  }

  /**
   * @since 4.1
   */
  handleCloseDialog = () => {
    /* trigger router */
    this.props.history.push(this.props.closeRoute || '/')
  }

  /**
   * @since 4.1
   */
  render () {
    return (
      <button
        data-test='component-templateCloseDialog'
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

export default withRouter(TemplateCloseDialog)
