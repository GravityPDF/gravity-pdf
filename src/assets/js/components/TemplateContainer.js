import React from 'react'
import TemplateCloseDialog from './TemplateCloseDialog'

export default React.createClass({

  propTypes: {
    header: React.PropTypes.oneOfType([ React.PropTypes.string, React.PropTypes.element ]),
    footer: React.PropTypes.oneOfType([ React.PropTypes.string, React.PropTypes.element ]),
    children: React.PropTypes.node.isRequired,
    closeRoute: React.PropTypes.string,
  },

  componentDidMount() {
    document.addEventListener('focus', this.handleFocus, true)

    /* Add focus if not currently applied to search box */
    if (document.activeElement && document.activeElement.className !== 'wp-filter-search') {
      this.container.focus()
    }
  },

  componentWillUnmount() {
    document.removeEventListener('focus', this.handleFocus, true)
  },

  handleFocus(e) {
    if (!this.container.contains(e.target)) {
      e.stopPropagation()
      this.container.focus()
    }
  },

  render() {
    const header = this.props.header,
      footer = this.props.footer,
      children = this.props.children,
      closeRoute = this.props.closeRoute

    return (
      <div ref={node => this.container = node} tabIndex="140">
        <div className="backdrop theme-backdrop"></div>
        <div className="container theme-wrap">
          <div className="theme-header">
            {header}
            <TemplateCloseDialog closeRoute={closeRoute}/>
          </div>

          <div
            id="gfpdf-template-container"
            className="theme-about wp-clearfix theme-browser rendered">
            {children}
          </div>

          {footer}
        </div>
      </div>
    )
  }
})

