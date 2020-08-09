import PropTypes from 'prop-types'
import React from 'react'
import CloseDialog from '../Modal/CloseDialog'

/**
 * Renders our Advanced Template Selector container which is shared amongst the components
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class Container extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    header: PropTypes.oneOfType([PropTypes.string, PropTypes.element]),
    footer: PropTypes.oneOfType([PropTypes.string, PropTypes.element]),
    children: PropTypes.node.isRequired,
    closeRoute: PropTypes.string
  }

  /**
   * @since 4.1
   */
  constructor (props) {
    super(props)
    this.handleFocus = this.handleFocus.bind(this)
  }

  /**
   * On mount, add focus event to document option on mount
   * Also, if focus isn't currently applied to the search box we'll apply it
   * to our container to help with tabbing between elements
   *
   * @since 4.1
   */
  componentDidMount () {
    document.addEventListener('focus', this.handleFocus, true)

    /* Add focus if not currently applied to search box */
    if (document.activeElement && document.activeElement.className !== 'wp-filter-search') {
      this.container.focus()
    }
  }

  /**
   * Cleanup our document event listeners
   *
   * @since 4.1
   */
  componentWillUnmount () {
    document.removeEventListener('focus', this.handleFocus, true)
  }

  /**
   * When a focus event is fired and it's not apart of any DOM elements in our
   * container we will focus the container instead. In most cases this keeps the focus from
   * jumping outside our Template Container and allows for better keyboard navigation.
   *
   * @param e
   *
   * @since 4.1
   */
  handleFocus (e) {
    if (!this.container.contains(e.target)) {
      e.stopPropagation()
      this.container.focus()
    }
  }

  /**
   * @since 4.1
   */
  render () {
    const header = this.props.header
    const footer = this.props.footer
    const children = this.props.children
    const closeRoute = this.props.closeRoute

    return (
      <div
        data-test='component-templateContainer'
        ref={node => (this.container = node)}
        tabIndex='140'
      >
        <div className='backdrop theme-backdrop' />
        <div className='container theme-wrap'>
          <div className='theme-header'>
            {header}
            <CloseDialog closeRoute={closeRoute} />
          </div>

          <div
            id='gfpdf-template-container'
            className='theme-about wp-clearfix theme-browser rendered'
          >
            {children}
          </div>

          {footer}
        </div>
      </div>
    )
  }
}

export default Container
