import React from 'react'
import { connect } from 'react-redux'
import { hashHistory } from 'react-router'
import { List } from 'immutable'

export const TemplateHeaderNavigation = React.createClass({

  propTypes: {
    templates: React.PropTypes.object.isRequired,
    templateIndex: React.PropTypes.number.isRequired,
    isFirst: React.PropTypes.bool,
    isLast: React.PropTypes.bool,
  },

  componentDidMount() {
    window.addEventListener('keydown', this.handleKeyPress, false)
  },

  componentWillUnmount() {
    window.removeEventListener('keydown', this.handleKeyPress, false)
  },

  previousTemplate(e) {
    e.preventDefault()
    e.stopPropagation()

    const prevId = this.props.templates.get(this.props.templateIndex - 1).get('id')

    if (prevId) {
      hashHistory.push('template/' + prevId)
    }
  },

  nextTemplate(e) {
    e.preventDefault()
    e.stopPropagation()

    const nextId = this.props.templates.get(this.props.templateIndex + 1).get('id')

    if (nextId) {
      hashHistory.push('template/' + nextId)
    }
  },

  handleKeyPress(e) {
    /* Left Arrow */
    if (!this.props.isFirst && e.keyCode === 37) {
      this.props.templates.get(this.props.templateIndex - 1).get('id')
      this.previousTemplate(e)
    }

    /* Right Arrow */
    if (!this.props.isLast && e.keyCode === 39) {
      this.props.templates.get(this.props.templateIndex + 1).get('id')
      this.nextTemplate(e)
    }
  },

  render() {

    const isFirst = this.props.isFirst
    const isLast = this.props.isLast

    let baseClass = List([ 'dashicons', 'dashicons-no' ])

    let prevClass = baseClass.push('left')
    let nextClass = baseClass.push('right')
    prevClass = (isFirst) ? prevClass.push('disabled') : prevClass
    nextClass = (isLast) ? nextClass.push('disabled') : nextClass

    let leftDisabled = (isFirst) ? 'disabled' : ''
    let rightDisabled = (isLast) ? 'disabled' : ''

    return (
      <span>
        <button
          onClick={this.previousTemplate}
          onKeyDown={this.handleKeyPress}
          className={prevClass.join(' ')}
          tabIndex="141"
          disabled={leftDisabled}>
            <span
              className="screen-reader-text">
              Show previous template
            </span>
        </button>

        <button
          onClick={this.nextTemplate}
          onKeyDown={this.handleKeyPress}
          className={nextClass.join(' ')}
          tabIndex="141"
          disabled={rightDisabled}>
          <span
            className="screen-reader-text">
            Show next template
          </span>
        </button>
      </span>
    )
  }
})

const MapStateToProps = (state, props) => {
  /* check if the current template is the first or last in our templates */
  const templates = props.templates
  const currentTemplateId = props.template.get('id')
  const first = templates.first().get('id')
  const last = templates.last().get('id')

  return {
    isFirst: (first === currentTemplateId) ? true : false,
    isLast: (last === currentTemplateId) ? true : false,
  }
}

export default connect(MapStateToProps)(TemplateHeaderNavigation)

