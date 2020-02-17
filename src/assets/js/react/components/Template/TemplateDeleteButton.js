import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { addTemplate, deleteTemplate, templateProcessing, clearTemplateProcessing } from '../../actions/templates'
import { withRouter } from 'react-router-dom'

/**
 * Renders a delete button which then queries our server and
 * removes the selected PDF template
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2020, Blue Liquid Designs

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
export class TemplateDeleteButton extends React.Component {
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
   * Fires appropriate action based on Redux store data
   *
   * @param {Object} nextProps
   *
   * @since 4.1
   */
  componentWillReceiveProps (nextProps) {
    nextProps.getTemplateProcessing === 'success' && this.props.history.push('/template')
    nextProps.getTemplateProcessing === 'failed' && this.ajaxFailed()
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
    const errorTemplate = { ...this.props.template, 'error': this.props.templateDeleteErrorText }
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
        onClick={callback}
        href="#"
        tabIndex="150"
        className="button button-secondary delete-theme">
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
const mapDispatchToProps = dispatch => {
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
