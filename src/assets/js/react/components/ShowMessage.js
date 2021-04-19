/* Dependencies */
import $ from 'jquery'
import React, { Component } from 'react'
import PropTypes from 'prop-types'

/**
 * Renders a message or error, with the option to self-clear itself
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class ShowMessage extends Component {
  /**
   * Pass the "dismissable" prop to enable auto-clearing
   *
   * @returns {{delay: number, dismissable: boolean}}
   *
   * @since 4.1
   */
  static defaultProps = {
    delay: 4000,
    dismissable: false
  }

  /**
   * @since 4.1
   */
  static propTypes = {
    text: PropTypes.string.isRequired,
    error: PropTypes.bool,
    delay: PropTypes.number,
    dismissable: PropTypes.bool,
    dismissableCallback: PropTypes.func
  }

  /**
   * @returns {{visible: boolean}}
   *
   * @since 4.1
   */
  state = {
    visible: true
  }

  /**
   * On mount, maybe set dismissable timer
   *
   * @since 4.1
   */
  componentDidMount () {
    this.shouldSetTimer()
  }

  /**
   * If component did update, call reset state function
   *
   * @param prevProps
   * @param prevState
   *
   * @since 4.1
   */
  componentDidUpdate (prevProps, prevState) {
    if (!prevState.visible) {
      this.resetState()
    }
  }

  /**
   * Clear timeout on unmount
   *
   * @since 4.1
   */
  componentWillUnmount () {
    if (this.props.dismissable) {
      clearTimeout(this._timer)
    }
  }

  /**
   * Check if we should make the message auto-dismissable
   *
   * @since 4.1
   */
  shouldSetTimer = () => {
    if (this.props.dismissable) {
      this.setTimer()
    }
  }

  /**
   * Slide message up after "X" milliseconds (see props.delay)
   * and triggers callback if passed in (see props.dismissableCallback)
   *
   * Also clears the initial timeout if called multiple times before removal
   *
   * @since 4.1
   */
  setTimer = () => {
    // clear any existing timer
    this._timer = this._timer !== null ? clearTimeout(this._timer) : null

    // hide after `delay` milliseconds
    this._timer = setTimeout(() => {
      $(this._message)
        .removeClass('inline')
        .slideUp(400, () => {
          $(this._message).removeAttr('style')
          this.setState({ visible: false })
          this._timer = null

          if (this.props.dismissableCallback) {
            this.props.dismissableCallback()
          }
        })
    }, this.props.delay)
  }

  /**
   * Resets our state and timer
   *
   * @since 4.1
   */
  resetState = () => {
    this.setState({ visible: true })
    this.shouldSetTimer()
  }

  /**
   * Renders our message or error
   *
   * @since 4.1
   */
  render () {
    const { text, error } = this.props

    let classes = 'notice inline'

    if (error) {
      classes = classes + ' error'
    }

    return this.state.visible ? (
      <div
        data-test='component-showMessage'
        ref={message => (this._message = message)}
        className={classes}
      >
        <p>{text}</p>
      </div>
    ) : <div />
  }
}

export default ShowMessage
