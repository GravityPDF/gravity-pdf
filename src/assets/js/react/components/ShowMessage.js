import PropTypes from 'prop-types';
import React from 'react'
import $ from 'jquery'

/**
 * Renders a message or error, with the option to self-clear itself
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
 * React Component
 *
 * @since 4.1
 */
class showMessage extends React.Component {
  /**
   * Pass the "dismissable" prop to enable auto-clearing
   *
   * @returns {{delay: number, dismissable: boolean}}
   *
   * @since 4.1
   */
  static defaultProps = {
    delay: 4000,
    dismissable: false,
  };

  /**
   * @since 4.1
   */
  static propTypes = {
    text: PropTypes.string.isRequired,
    error: PropTypes.bool,

    delay: PropTypes.number,
    dismissable: PropTypes.bool,
    dismissableCallback: PropTypes.func,
  };

  /**
   * @returns {{visible: boolean}}
   *
   * @since 4.1
   */
  state = {
    visible: true
  };

  /**
   * Resets our state and timer when new props received
   *
   * @since 4.1
   */
  componentWillReceiveProps() {
    this.setState({ visible: true })
    this.shouldSetTimer()
  }

  /**
   * On mount, maybe set dismissable timer
   *
   * @since 4.1
   */
  componentDidMount() {
    this.shouldSetTimer()
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
  };

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
    this._timer != null ? clearTimeout(this._timer) : null

    // hide after `delay` milliseconds
    this._timer = setTimeout(() => {

      $(this._message)
        .removeClass('inline')
        .slideUp(400, () => {
          $(this._message).removeAttr('style')
          this.setState({ visible: false })
          this._timer = null

          if(this.props.dismissableCallback) {
            this.props.dismissableCallback()
          }
        })

    }, this.props.delay)
  };

  /**
   * Clear timeout on unmount
   *
   * @since 4.1
   */
  componentWillUnmount() {
    if (this.props.dismissable) {
      clearTimeout(this._timer)
    }
  }

  /**
   * Renders our message or error
   *
   * @since 4.1
   */
  render() {
    const { text, error } = this.props

    let classes = 'notice inline'

    if (error) {
      classes = classes + ' error'
    }

    return this.state.visible ?
      (
        <div ref={(message) => this._message = message} className={classes}>
          <p>{text}</p>
        </div>
      ) : <div />
  }
}

export default showMessage