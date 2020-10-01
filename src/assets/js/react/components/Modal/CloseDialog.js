import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { withRouter } from 'react-router-dom'
import { getCustomFontList, clearAddFontMsg } from '../../actions/fontManager'
import { toggleUpdateFont } from '../../utilities/toggleUpdateFont'
import associatedFontManagerSelectBox from '../../utilities/associatedFontManagerSelectBox'

/**
 * Renders our close dialog element
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * React Component
 *
 * @since 6.0
 */
export class CloseDialog extends React.Component {
  /**
   * @since 6.0
   */
  static propTypes = {
    id: PropTypes.string,
    closeRoute: PropTypes.string,
    getCustomFontList: PropTypes.func.isRequired,
    clearAddFontMsg: PropTypes.func.isRequired,
    templateList: PropTypes.arrayOf(PropTypes.object).isRequired,
    fontList: PropTypes.arrayOf(PropTypes.object).isRequired,
    selectedFont: PropTypes.string.isRequired,
    msg: PropTypes.object.isRequired,
    history: PropTypes.object.isRequired
  }

  /**
   * Assign keydown listener to document on mount
   *
   * @since 6.0
   */
  componentDidMount () {
    document.addEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Check for new added template and fetch new fontList to trigger a request of
   * updated font manager select box
   *
   * @param prevProps
   */
  componentDidUpdate (prevProps) {
    const { templateList, getCustomFontList } = this.props

    if (prevProps.templateList !== templateList) {
      getCustomFontList()
    }
  }

  /**
   * Remove keydown listener to document on mount
   *
   * @since 6.0
   */
  componentWillUnmount () {
    document.removeEventListener('keydown', this.handleKeyPress, false)

    const { fontList, selectedFont } = this.props
    const tabLocation = window.location.search.substr(window.location.search.lastIndexOf('=') + 1)

    /* Ensure associated font manager select box has the latest data */
    if (tabLocation !== 'tools') {
      return associatedFontManagerSelectBox(fontList, selectedFont)
    }
  }

  /**
   * Check if Escape key pressed and current event target isn't our search box,
   * or the search box is blank already
   *
   * @param {Object} e Event
   *
   * @since 6.0
   */
  handleKeyPress = e => {
    const { id, history, clearAddFontMsg, msg: { success, error } } = this.props

    /* Close font manager 'Update Font' column first */
    if (e.keyCode === 27 && id) {
      /* Remove previous msg */
      if ((success && success.addFont) || (error && error.addFont)) {
        clearAddFontMsg()
      }

      return toggleUpdateFont(history)
    }

    /* Close modal */
    if (e.keyCode === 27 && (e.target.className !== 'wp-filter-search' || e.target.value === '')) {
      this.handleCloseDialog()
    }
  }

  /**
   * @since 6.0
   */
  handleCloseDialog = () => {
    /* trigger router */
    this.props.history.push(this.props.closeRoute || '/')
  }

  /**
   * @since 6.0
   */
  render () {
    return (
      <button
        data-test='component-CloseDialog'
        className='close dashicons dashicons-no'
        tabIndex='142'
        onClick={this.handleCloseDialog}
        aria-label='close'
      >
        <span className='screen-reader-text'>Close dialog</span>
      </button>
    )
  }
}

const mapStateToProps = state => ({
  templateList: state.template.list,
  fontList: state.fontManager.fontList,
  selectedFont: state.fontManager.selectedFont,
  msg: state.fontManager.msg
})

export default withRouter(connect(mapStateToProps, {
  getCustomFontList,
  clearAddFontMsg
})(CloseDialog))
