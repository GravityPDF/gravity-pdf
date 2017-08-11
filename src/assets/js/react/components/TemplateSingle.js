import PropTypes from 'prop-types';
import React from 'react'
import { connect } from 'react-redux'

import getTemplates from '../selectors/getTemplates'

import TemplateContainer from './TemplateContainer'
import TemplateHeaderNavigation from './TemplateHeaderNavigation'
import TemplateFooterActions from './TemplateFooterActions'
import TemplateScreenshots from './TemplateScreenshots'
import ShowMessage from './ShowMessage'

import {
  CurrentTemplate,
  Name,
  Author,
  Group,
  Description,
  Tags
} from './TemplateSingleComponents'

/**
 * Renders a single PDF template, which get displayed on the /template/:id page.
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
export class TemplateSingle extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    route: PropTypes.object,

    template: PropTypes.object,
    activeTemplate: PropTypes.string,
    templateIndex: PropTypes.number,
    templates: PropTypes.object,
  };

  /**
   * Ensure the component doesn't try and re-render when a template isn't found
   *
   * @param nextProps
   * @param nextState
   *
   * @Internal This problem seems to be prevelant due to a race condition when deleting a template and updating the URL
   *
   * @since 4.2
   */
  shouldComponentUpdate (nextProps, nextState) {
    if (nextProps.template == null) {
      return false
    }

    return true
  };

  /**
   * @since 4.1
   */
  render () {
    const item = this.props.template
    const isCurrentTemplate = this.props.activeTemplate === item.get('id')

    /* Assign our header / footer components to constants */
    const header = <TemplateHeaderNavigation
      template={item}
      templateIndex={this.props.templateIndex}
      templates={this.props.templates}
      showPreviousTemplateText={this.props.showPreviousTemplateText}
      showNextTemplateText={this.props.showNextTemplateText}/>

    const footer = <TemplateFooterActions
      template={item}
      isActiveTemplate={isCurrentTemplate}

      ajaxUrl={this.props.ajaxUrl}
      ajaxNonce={this.props.ajaxNonce}

      activateText={this.props.activateText}
      pdfWorkingDirPath={this.props.pdfWorkingDirPath}
      templateDeleteText={this.props.templateDeleteText}
      templateConfirmDeleteText={this.props.templateConfirmDeleteText}
      templateDeleteErrorText={this.props.templateDeleteErrorText}
    />

    /* Display our Single Template container */
    return (
      <TemplateContainer header={header} footer={footer} closeRoute="/template">
        <div id="gfpdf-template-detail-view" className="gfpdf-template-detail">
          <TemplateScreenshots image={item.get('screenshot')}/>

          <div className="theme-info">
            <CurrentTemplate isCurrentTemplate={isCurrentTemplate} label={this.props.currentTemplateText}/>
            <Name name={item.get('template')} version={item.get('version')}
                  versionLabel={this.props.versionText}/>
            <Author author={item.get('author')} uri={item.get('author uri')}/>
            <Group group={item.get('group')} label={this.props.groupText}/>

            {item.get('long_message') ? <ShowMessage text={item.get('long_message')}/> : null}
            {item.get('long_error') ? <ShowMessage text={item.get('long_error')} error={true}/> : null}

            <Description desc={item.get('description')}/>
            <Tags tags={item.get('tags')} label={this.props.tagsText}/>
          </div>
        </div>
      </TemplateContainer>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 * @param {Object} props The current React props
 *
 * @returns {{template: Immutable Map, templateIndex: number, templates: Immutable List, activeTemplate: string}}
 *
 * @since 4.1
 */
const MapStateToProps = (state, props) => {

  /* found our selected template */
  const templates = getTemplates(state)
  const id = props.match.params.id

  const findCurrentTemplate = (item) => {
    return (item.get('id') === id)
  }

  return {
    template: templates.find(findCurrentTemplate),
    templateIndex: templates.findIndex(findCurrentTemplate),
    templates: templates,
    activeTemplate: state.template.activeTemplate,
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(MapStateToProps)(TemplateSingle)