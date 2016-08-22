import React from 'react'
import { connect } from 'react-redux'
import TemplateScreenshot from './TemplateScreenshot'
import { hashHistory } from 'react-router'

import {
  TemplateDetails,
  Group
} from './TemplateListItemComponents'

import { Name } from './TemplateSingleComponents'
import TemplateActivateButton from './TemplateActivateButton'

export const TemplateListItem = React.createClass({

  propTypes: {
    template: React.PropTypes.object,
    activeTemplate: React.PropTypes.string,
    activateText: React.PropTypes.string,
  },

  maybeShowDetailedTemplate(e) {
    /* Show detailed template when the Enter key is pressed and the active element doesn't include a 'button' class */
    if (e.keyCode === 13 && (e.target.className.indexOf('button') === -1)) {
      this.showDetailedTemplate()
    }
  },

  showDetailedTemplate() {
    hashHistory.push('/template/' + this.props.template.get('id'))
  },

  render() {
    const item = this.props.template
    const isActiveTemplate = this.props.activeTemplate === item.get('id')
    const activeTemplate = (isActiveTemplate) ? 'active theme' : 'theme'

    return (
      <div
        onClick={this.showDetailedTemplate}
        onKeyDown={this.maybeShowDetailedTemplate}
        className={activeTemplate}
        data-slug={item.get('id')}
        tabIndex="150">

        <TemplateScreenshot image={item.get('screenshot')}/>
        <TemplateDetails />
        <Group group={item.get('group')}/>
        <Name name={item.get('template')}/>

        <div className="theme-actions">
          {!isActiveTemplate ? <TemplateActivateButton template={this.props.template} activateText={this.props.activateText}/> : null}
        </div>
      </div>
    )
  }
})

const mapStateToProps = (state) => {
  return {
    activeTemplate: state.template.activeTemplate
  }
}

export default connect(mapStateToProps)(TemplateListItem)