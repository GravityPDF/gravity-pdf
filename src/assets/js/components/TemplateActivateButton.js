import React from 'react'
import { connect } from 'react-redux'
import { selectTemplate } from '../actions/templates'
import { hashHistory } from 'react-router'

export const TemplateActivateButton = React.createClass({

  propTypes: {
    template: React.PropTypes.object,
    onTemplateSelect: React.PropTypes.func,
    activateText: React.PropTypes.string,
  },

  selectTemplate(e) {
    e.preventDefault()
    e.stopPropagation()
    hashHistory.push('')

    this.props.onTemplateSelect(this.props.template.get('id'))
  },

  render() {

    return (
      <a
        onClick={this.selectTemplate}
        href="#"
        tabIndex="150"
        className="button button-primary activate">
        {this.props.activateText}
      </a>
    )
  }
})

const mapDispatchToProps = (dispatch) => {
  return {
    onTemplateSelect: (id) => {
      dispatch(selectTemplate(id))
    }
  }
}

export default connect(null, mapDispatchToProps)(TemplateActivateButton)

