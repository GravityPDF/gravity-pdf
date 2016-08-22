import React from 'react'
import { connect } from 'react-redux'

import getTemplates from '../selectors/getTemplates'

import TemplateContainer from './TemplateContainer'
import TemplateListItem from './TemplateListItem'
import TemplateSearch from './TemplateSearch'
import TemplateHeaderTitle from './TemplateHeaderTitle'

export const TemplateList = React.createClass({

  propTypes: {
    templates: React.PropTypes.object,
    route: React.PropTypes.object
  },

  render() {
    const header = <TemplateHeaderTitle />

    return (
      <TemplateContainer header={header} closeRoute="/">
        <TemplateSearch />
        <div>
          {
            this.props.templates.map((value, index) => {
              return <TemplateListItem key={index} template={value} activateText={this.props.route.activateText}/>
            })
          }

          {!this.props.templates.size ? this.noTemplates() : '' }
        </div>
      </TemplateContainer>
    )
  },

  noTemplates() {
    return (
      <p className="no-themes">{this.props.route.noTemplateFoundText}</p>
    )
  }
})

const mapStateToProps = (state) => {
  return {
    templates: getTemplates(state)
  }
}

export default connect(mapStateToProps)(TemplateList)