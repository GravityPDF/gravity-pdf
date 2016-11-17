import React from 'react'
import { hashHistory } from 'react-router'

export default React.createClass({

  propTypes: {
    buttonText: React.PropTypes.string,
  },

  handleClick(e) {
    /*
     * Handle weird bug in React where the button click event fires when enter is pressed
     * on non-react components
     */
    if( document.activeElement && this.button === document.activeElement ) {
      e.preventDefault()
      e.stopPropagation()

      /* trigger router */
      hashHistory.push('/template')
    }
  },

  render() {
    return (
      <button
        id="fancy-template-selector"
        className="button gfpdf-button"
        onClick={this.handleClick}
        ref={node => this.button = node}
      >
        {this.props.buttonText}
      </button>
    )
  }
})