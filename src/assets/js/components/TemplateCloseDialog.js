import React from 'react'
import { hashHistory } from 'react-router'

/* For any empty routes we'll just output a blank container */
const TemplateCloseDialog = React.createClass({
  propTypes: {
    closeRoute: React.PropTypes.string
  },

  componentDidMount() {
    document.addEventListener('keydown', this.handleKeyPress, false)
  },

  componentWillUnmount() {
    document.removeEventListener('keydown', this.handleKeyPress, false)
  },

  handleKeyPress(e) {
    /* Escape Key */
    if (e.keyCode === 27 && (e.target.className !== 'wp-filter-search' || e.target.value === '')) {
      this.closeDialog()
    }
  },

  closeDialog() {
    /* trigger router */
    hashHistory.push(this.props.closeRoute || '/')
  },

  render() {
    return (
      <button
        className="close dashicons dashicons-no"
        tabIndex="142"
        onClick={this.closeDialog}
        onKeyDown={this.handleKeyPress}
        aria-label="close">
        <span className="screen-reader-text">Close dialog</span>
      </button>
    )
  }
})

export default TemplateCloseDialog