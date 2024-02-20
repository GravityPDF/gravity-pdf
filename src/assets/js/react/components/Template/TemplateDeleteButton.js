/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { withRouter } from 'react-router-dom'
/* Redux actions */
import { addTemplate, deleteTemplate, templateProcessing, clearTemplateProcessing } from '../../actions/templates'

/**
 * Renders a delete button which then queries our server and
 * removes the selected PDF template
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateDeleteButton extends Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    template: PropTypes.object,
    addTemplate: PropTypes.func,
    onTemplateDelete: PropTypes.func,
    callbackFunction: PropTypes.func,
    templateProcessing: PropTypes.func,
    clearTemplateProcessing: PropTypes.func,
    getTemplateProcessing: PropTypes.string,
    history: PropTypes.object,
    buttonText: PropTypes.string,
    templateConfirmDeleteText: PropTypes.string,
    templateDeleteErrorText: PropTypes.string
  }

  /**
   * If component did update, fires appropriate action based on Redux store data
   *
   * @since 4.1
   */
  componentDidUpdate () {
    const { getTemplateProcessing, history } = this.props

    if (getTemplateProcessing === 'success') {
      history.push('/template')
    }

    if (getTemplateProcessing === 'failed') {
      this.ajaxFailed()
    }
  }

  /**
   * Display a confirmation window asking user to verify they want template deleted.
   *
   * Once verified, we make an AJAX call to the server requesting template to be deleted.
   *
   * Before we receive the response we remove the PDF template automatically and update the
   * URL to /template. If the AJAX call fails the PDF template gets restored to our list with
   * an appropriate error message (it feels snapper this way).
   *
   * @param {Object} e Event
   */
  deleteTemplate = (e) => {
    e.preventDefault()
    e.stopPropagation()
    if (window.confirm(this.props.templateConfirmDeleteText)) {
      /* POST the PDF template to our endpoint for processing */
      this.props.templateProcessing(this.props.template.id)

      this.props.getTemplateProcessing === 'success' && this.props.history.push('/template')
      this.props.onTemplateDelete(this.props.template.id)
    }
  }

  /**
   * If the server cannot delete the template we re-add the template to our list
   * and display an appropriate inline error message
   *
   * @since 4.1
   */
  ajaxFailed = () => {
    const errorTemplate = { ...this.props.template, error: this.props.templateDeleteErrorText }
    this.props.addTemplate(errorTemplate)

    this.props.history.push('/template')
    this.props.clearTemplateProcessing()
  }

  /**
   * @since 4.1
   */
  render () {
    const callback = (this.props.callbackFunction) ? this.props.callbackFunction : this.deleteTemplate

    return (
      <a
        data-test='component-templateDeleteButton'
        onClick={callback}
        href='#'
        tabIndex='150'
        className='button button-secondary delete-theme ed_button'
        aria-label={this.props.buttonText + ' ' + GFPDF.template}
      >
        {this.props.buttonText}
      </a>
    )
  }
}

/**
 * Map Redux state to props
 *
 * @param state
 * @returns {{getTemplateProcessing: String}}
 *
 * @since 5.2
 */
const mapStateToProps = state => ({
  getTemplateProcessing: state.template.templateProcessing
})

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{addTemplate: (function(template)), onTemplateDelete: (function(id=string)), templateProcessing: (function(templateId=string)), clearTemplateProcessingValue: (function())}}
 *
 * @since 4.1
 */
export const mapDispatchToProps = dispatch => {
  return {
    addTemplate: (template) => {
      dispatch(addTemplate(template))
    },

    onTemplateDelete: (id) => {
      dispatch(deleteTemplate(id))
    },

    templateProcessing: (templateId) => {
      dispatch(templateProcessing(templateId))
    },

    clearTemplateProcessing: () => {
      dispatch(clearTemplateProcessing())
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(mapStateToProps, mapDispatchToProps)(TemplateDeleteButton))
