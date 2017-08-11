import PropTypes from 'prop-types';
import React from 'react'
import { connect } from 'react-redux'
import { withRouter } from 'react-router-dom'
import { updateTemplateParam } from '../actions/templates'

import TemplateScreenshot from './TemplateScreenshot'
import ShowMessage from './ShowMessage'

import {
  TemplateDetails,
  Group
} from './TemplateListItemComponents'

import { Name } from './TemplateSingleComponents'
import TemplateActivateButton from './TemplateActivateButton'

/**
 * Display the individual template item for usage our template list
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
export class TemplateListItem extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    template: PropTypes.object,

    activeTemplate: PropTypes.string,
    updateTemplateParam: PropTypes.func,

    activateText: PropTypes.string,
    templateDetailsText: PropTypes.string,
  };

  /**
   * Check if the Enter key is pressed and not focused on a button
   * then display the template details page
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  maybeShowDetailedTemplate = (e) => {
    /* Show detailed template when the Enter key is pressed and the active element doesn't include a 'button' class */
    if (e.keyCode === 13 && (e.target.className.indexOf('button') === -1)) {
      this.showDetailedTemplate()
    }
  };

  /**
   * Update the URL to show the PDF template details page
   *
   * @since 4.1
   */
  showDetailedTemplate = () => {
    this.props.history.push('/template/' + this.props.template.get('id'))
  };

  /**
   * Call Redux action to remove any stored messages for this template
   *
   * @since 4.1
   */
  removeMessage = () => {
    this.props.updateTemplateParam(this.props.template.get('id'), 'message', null)
  };

  /**
   * @since 4.1
   */
  render() {
    const item = this.props.template
    const isActiveTemplate = this.props.activeTemplate === item.get('id')
    const isCompatible = item.get('compatible')
    const activeTemplate = (isActiveTemplate) ? 'active theme' : 'theme'

    return (
        <div
          onClick={this.showDetailedTemplate}
          onKeyDown={this.maybeShowDetailedTemplate}
          className={activeTemplate}
          data-slug={item.get('id')}
          tabIndex="150">

          <TemplateScreenshot image={item.get('screenshot')}/>
          {item.get('error') ? <ShowMessage text={item.get('error')} error={true}/> : null}
          {item.get('message') ? <ShowMessage text={item.get('message')} dismissableCallback={this.removeMessage} dismissable={true} delay={12000} /> : null}

          <TemplateDetails label={this.props.templateDetailsText} />
          <Group group={item.get('group')}/>
          <Name name={item.get('template')}/>

          <div className="theme-actions">
            {!isActiveTemplate && isCompatible ?
              <TemplateActivateButton template={this.props.template} buttonText={this.props.activateText}/> : null}
          </div>
        </div>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 *
 * @returns {{activeTemplate: string}}
 *
 * @since 4.1
 */
const mapStateToProps = (state) => {
  return {
    activeTemplate: state.template.activeTemplate
  }
}

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{updateTemplateParam: (function(id=string, name=string, value=string))}}
 *
 * @since 4.1
 */
const mapDispatchToProps = (dispatch) => {
  return {
    updateTemplateParam: (id, name, value) => {
      dispatch(updateTemplateParam(id, name, value))
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(mapStateToProps, mapDispatchToProps)(TemplateListItem))