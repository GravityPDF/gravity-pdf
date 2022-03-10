/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { withRouter } from 'react-router-dom'

/**
 * Renders the template navigation header that get displayed on the
 * /template/:id pages.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateHeaderNavigation extends Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    templates: PropTypes.array.isRequired,
    templateIndex: PropTypes.number.isRequired,
    history: PropTypes.object,
    isFirst: PropTypes.bool,
    isLast: PropTypes.bool,
    showPreviousTemplateText: PropTypes.string,
    showNextTemplateText: PropTypes.string
  }

  /**
   * Add window event listeners
   *
   * @since 4.1
   */
  componentDidMount () {
    window.addEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Cleanup window event listeners
   *
   * @since 4.1
   */
  componentWillUnmount () {
    window.removeEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Attempt to get the previous template in our list and update the URL
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handlePreviousTemplate = (e) => {
    e.preventDefault()
    e.stopPropagation()

    const prevId = this.props.templates[this.props.templateIndex - 1].id

    if (prevId) {
      this.props.history.push('/template/' + prevId)
    }
  }

  /**
   * Attempt to get the next template in our list and update the URL
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handleNextTemplate = (e) => {
    e.preventDefault()
    e.stopPropagation()

    const nextId = this.props.templates[this.props.templateIndex + 1].id

    if (nextId) {
      this.props.history.push('/template/' + nextId)
    }
  }

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
      this.handlePreviousTemplate(e)
    }

    /* Right Arrow */
    if (!this.props.isLast && e.keyCode === 39) {
      this.handleNextTemplate(e)
    }
  }

  /**
   * @since 4.1
   */
  render () {
    /*
     * Work our the correct classes and attributes for our left and right arrows
     * based on if we are currently showing the first or last templates
     */
    const isFirst = this.props.isFirst
    const isLast = this.props.isLast

    const prevClass = (isFirst) ? 'dashicons dashicons-no left disabled' : 'dashicons dashicons-no left'
    const nextClass = (isLast) ? 'dashicons dashicons-no right disabled' : 'dashicons dashicons-no right'

    const leftDisabled = (isFirst) ? 'disabled' : ''
    const rightDisabled = (isLast) ? 'disabled' : ''

    return (
      <span data-test='component-templateHeaderNavigation'>
        <button
          data-test='component-showPreviousTemplateButton'
          onClick={this.handlePreviousTemplate}
          onKeyDown={this.handleKeyPress}
          className={prevClass}
          tabIndex='141'
          disabled={leftDisabled}
        >
          <span
            className='screen-reader-text'
          >
            {this.props.showPreviousTemplateText}
          </span>
        </button>

        <button
          data-test='component-showNextTemplateButton'
          onClick={this.handleNextTemplate}
          onKeyDown={this.handleKeyPress}
          className={nextClass}
          tabIndex='141'
          disabled={rightDisabled}
        >
          <span
            className='screen-reader-text'
          >
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
  const currentTemplateId = props.template.id
  const lastTemplate = templates.length - 1
  const first = templates[0].id
  const last = templates[lastTemplate].id

  return {
    isFirst: first === currentTemplateId,
    isLast: last === currentTemplateId
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(MapStateToProps)(TemplateHeaderNavigation))
