import React from 'react'
import { connect } from 'react-redux'

import getTemplates from '../selectors/getTemplates'

import TemplateContainer from './TemplateContainer'
import TemplateHeaderNavigation from './TemplateHeaderNavigation'
import TemplateFooterActions from './TemplateFooterActions'
import TemplateScreenshots from './TemplateScreenshots'

import {
  CurrentTemplate,
  Name,
  Author,
  Group,
  Description,
  Tags
} from './TemplateSingleComponents'

export const TemplateSingle = React.createClass({

  propTypes: {
    template: React.PropTypes.object,
    activeTemplate: React.PropTypes.string,
    templateIndex: React.PropTypes.number,
    templates: React.PropTypes.object,
  },

  render() {
    const item = this.props.template
    const isCurrentTemplate = this.props.activeTemplate === item.get('id')

    const header = <TemplateHeaderNavigation
      template={item}
      templateIndex={this.props.templateIndex}
      templates={this.props.templates}/>

    const footer = <TemplateFooterActions template={item} isActiveTemplate={isCurrentTemplate} activateText={this.props.route.activateText}/>

    return (
      <TemplateContainer header={header} footer={footer} closeRoute="/template">
        <div id="gfpdf-template-detail-view" className="gfpdf-template-detail">
          <TemplateScreenshots image={item.get('screenshot')}/>

          <div className="theme-info">
            <CurrentTemplate isCurrentTemplate={isCurrentTemplate}/>
            <Name name={item.get('template')} version={item.get('version')}/>
            <Author author={item.get('author')} uri={item.get('author uri')}/>
            <Group group={item.get('group')}/>
            <Description desc={item.get('description')}/>
            <Tags tags={item.get('tags')}/>
          </div>
        </div>
      </TemplateContainer>
    )
  }
})

const MapStateToProps = (state, props) => {

  /* found our selected template */
  const templates = getTemplates(state)
  const id = props.params.id

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

export default connect(MapStateToProps)(TemplateSingle)