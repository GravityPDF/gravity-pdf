import PropTypes from 'prop-types';
import React from 'react'
import TemplateActivateButton from './TemplateActivateButton'
import TemplateDeleteButton from './TemplateDeleteButton'

/**
 * Renders the template footer actions that get displayed on the
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
class TemplateFooterActions extends React.Component {
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
    templateDeleteErrorText: PropTypes.string,
  };

  /**
   * Check if the current PDF template is a core template or not (i.e is shipped with Gravity PDF)
   *
   * @param {Object} template Immutable Map
   *
   * @returns {boolean}
   *
   * @since 4.1
   */
  notCoreTemplate = (template) => {
    return template.get('path').indexOf(this.props.pdfWorkingDirPath) !== -1
  };

  /**
   * @since 4.1
   */
  render() {
    const template = this.props.template
    const isCompatible = template.get('compatible')

    return (
      <div className="theme-actions">
        {!this.props.isActiveTemplate && isCompatible ?
          <TemplateActivateButton
            template={template}
            buttonText={this.props.activateText}/>
          : null
        }

        {!this.props.isActiveTemplate && this.notCoreTemplate(template) ?
          <TemplateDeleteButton
            template={template}

            ajaxUrl={this.props.ajaxUrl}
            ajaxNonce={this.props.ajaxNonce}

            buttonText={this.props.templateDeleteText}
            templateConfirmDeleteText={this.props.templateConfirmDeleteText}
            templateDeleteErrorText={this.props.templateDeleteErrorText}/>
          : null
        }
      </div>
    )
  }
}

export default TemplateFooterActions

