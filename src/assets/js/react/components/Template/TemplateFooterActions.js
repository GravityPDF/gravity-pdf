/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
/* Components */
import TemplateActivateButton from './TemplateActivateButton'
import TemplateDeleteButton from './TemplateDeleteButton'

/**
 * Renders the template footer actions that get displayed on the
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
export class TemplateFooterActions extends Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    template: PropTypes.object.isRequired,
    isActiveTemplate: PropTypes.bool,
    ajaxUrl: PropTypes.string,
    ajaxNonce: PropTypes.string,
    activateText: PropTypes.string,
    pdfWorkingDirPath: PropTypes.string,
    templateDeleteText: PropTypes.string,
    templateConfirmDeleteText: PropTypes.string,
    templateDeleteErrorText: PropTypes.string
  }

  /**
   * Check if the current PDF template is a core template or not (i.e is shipped with Gravity PDF)
   *
   * @param {Object} template
   *
   * @returns {boolean}
   *
   * @since 4.1
   */
  notCoreTemplate = (template) => {
    return template.path.indexOf(this.props.pdfWorkingDirPath) !== -1
  }

  /**
   * @since 4.1
   */
  render () {
    const template = this.props.template
    const isCompatible = template.compatible

    return (
      <div
        data-test='component-templateFooterActions'
        className='theme-actions'
      >
        {!this.props.isActiveTemplate && isCompatible ? (
          <TemplateActivateButton
            template={template}
            buttonText={this.props.activateText}
          />
        ) : null}

        {!this.props.isActiveTemplate && this.notCoreTemplate(template) ? (
          <TemplateDeleteButton
            template={template}
            ajaxUrl={this.props.ajaxUrl}
            ajaxNonce={this.props.ajaxNonce}
            buttonText={this.props.templateDeleteText}
            templateConfirmDeleteText={this.props.templateConfirmDeleteText}
            templateDeleteErrorText={this.props.templateDeleteErrorText}
          />
        ) : null}
      </div>
    )
  }
}

export default TemplateFooterActions
