import PropTypes from 'prop-types';
import React from 'react'
import { connect } from 'react-redux'
import { withRouter } from 'react-router-dom'
import { List } from 'immutable'

/**
 * Renders the template navigation header that get displayed on the
 * /template/:id pages.
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
export class TemplateHeaderNavigation extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    templates: PropTypes.object.isRequired,
    templateIndex: PropTypes.number.isRequired,
    isFirst: PropTypes.bool,
    isLast: PropTypes.bool,

    showPreviousTemplateText: PropTypes.string,
    showNextTemplateText: PropTypes.string
  };

  /**
   * Add window event listeners
   *
   * @since 4.1
   */
  componentDidMount() {
    window.addEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Cleanup window event listeners
   *
   * @since 4.1
   */
  componentWillUnmount() {
    window.removeEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Attempt to get the previous template in our Immutable list and update the URL
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  previousTemplate = (e) => {
    e.preventDefault()
    e.stopPropagation()

    const prevId = this.props.templates.get(this.props.templateIndex - 1).get('id')

    if (prevId) {
      this.props.history.push('/template/' + prevId)
    }
  };

  /**
   * Attempt to get the next template in our Immutable list and update the URL
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  nextTemplate = (e) => {
    e.preventDefault()
    e.stopPropagation()

    const nextId = this.props.templates.get(this.props.templateIndex + 1).get('id')

    if (nextId) {
      this.props.history.push('/template/' + nextId)
    }
  };

  /**
   * Checks if the Left or Right arrow keys are pressed and fires appropriate functions
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handleKeyPress = (e) => {
    /* Left Arrow */
    if (!this.props.isFirst && e.keyCode === 37) {
      this.previousTemplate(e)
    }

    /* Right Arrow */
    if (!this.props.isLast && e.keyCode === 39) {
      this.nextTemplate(e)
    }
  };

  /**
   * @since 4.1
   */
  render() {

    /*
     * Work our the correct classes and attributes for our left and right arrows
     * based on if we are currently showing the first or last templates
     */
    const isFirst = this.props.isFirst
    const isLast = this.props.isLast

    let baseClass = List([ 'dashicons', 'dashicons-no' ])

    let prevClass = baseClass.push('left')
    let nextClass = baseClass.push('right')
    prevClass = (isFirst) ? prevClass.push('disabled') : prevClass
    nextClass = (isLast) ? nextClass.push('disabled') : nextClass

    let leftDisabled = (isFirst) ? 'disabled' : ''
    let rightDisabled = (isLast) ? 'disabled' : ''

    return (
      <span>
        <button
          onClick={this.previousTemplate}
          onKeyDown={this.handleKeyPress}
          className={prevClass.join(' ')}
          tabIndex="141"
          disabled={leftDisabled}>
            <span
              className="screen-reader-text">
              {this.props.showPreviousTemplateText}
            </span>
        </button>

        <button
          onClick={this.nextTemplate}
          onKeyDown={this.handleKeyPress}
          className={nextClass.join(' ')}
          tabIndex="141"
          disabled={rightDisabled}>
          <span
            className="screen-reader-text">
            {this.props.showNextTemplateText}
          </span>
        </button>
      </span>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 * @param {Object} props The current React props
 *
 * @returns {{isFirst: boolean, isLast: boolean}}
 *
 * @since 4.1
 */
const MapStateToProps = (state, props) => {
  /* Check if the current template is the first or last in our templates */
  const templates = props.templates
  const currentTemplateId = props.template.get('id')
  const first = templates.first().get('id')
  const last = templates.last().get('id')

  return {
    isFirst: first === currentTemplateId,
    isLast: last === currentTemplateId,
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(MapStateToProps)(TemplateHeaderNavigation))

