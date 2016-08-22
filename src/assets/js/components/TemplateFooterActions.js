import React from 'react'
import TemplateActivateButton from './TemplateActivateButton'

const TemplateActions = React.createClass({

  propTypes: {
    template: React.PropTypes.object,
    isActiveTemplate: React.PropTypes.bool,
    activateText: React.PropTypes.string,
  },

  render() {
    return (
      <div className="theme-actions">
        {!this.props.isActiveTemplate ? <TemplateActivateButton template={this.props.template} activateText={this.props.activateText}/> : null }
      </div>
    )
  }
})

export default TemplateActions

