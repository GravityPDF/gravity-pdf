/* Dependencies */
import React, { Component } from 'react'
import { connect } from 'react-redux'
import PropTypes from 'prop-types'
/* Redux actions */
import { resetSearchResult, searchFontList } from '../../actions/fontManager'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * SearchBox component
 *
 * @since 6.0
 */
export class SearchBox extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    id: PropTypes.string,
    searchResult: PropTypes.oneOfType([
      PropTypes.oneOf([null]).isRequired,
      PropTypes.arrayOf(PropTypes.object).isRequired
    ]),
    msg: PropTypes.object.isRequired,
    resetSearchResult: PropTypes.func.isRequired,
    searchFontList: PropTypes.func.isRequired
  }

  /**
   * Initialize component state
   *
   * @type {{ searchInput: string }}
   *
   * @since 6.0
   */
  state = {
    searchInput: ''
  }

  /**
   * On mount, Add focus event to document on mount
   *
   * @since 6.0
   */
  componentDidMount () {
    /* add focus to element */
    this.input.focus()
  }

  /**
   * If component did update and new props are received,
   * fires appropriate action based on redux store data
   *
   * @param prevProps: object
   *
   * @since 6.0
   */
  componentDidUpdate (prevProps) {
    const { id, searchResult, msg } = this.props

    /* Call the method resetSearchState() */
    if (prevProps.searchResult !== searchResult && !searchResult) {
      this.resetSearchState()
    }

    /* Clear search box after a successful font has been added */
    if (JSON.stringify(prevProps.msg) !== JSON.stringify(msg) && msg.success && id) {
      this.resetSearchState()
    }
  }

  /**
   * On component unmount, Call our redux action resetSearchResult()
   *
   * @since 6.0
   */
  componentWillUnmount () {
    if (this.state.searchInput !== '') {
      /* Call redux action resetSearchResult() */
      this.props.resetSearchResult()
    }
  }

  /**
   * Listen to search box input field change
   *
   * @param e: object
   *
   * @since 6.0
   */
  handleSearch = e => {
    const data = e.target.value

    this.setState({ searchInput: data })
    /* Call redux action searchFontList() */
    this.props.searchFontList(data)
  }

  /**
   * Reset component searchInput state
   *
   * @since 6.0
   */
  resetSearchState = () => {
    this.setState({ searchInput: '' })
  }

  /**
   * Display font manager search box UI
   *
   * @since 6.0
   */
  render () {
    return (
      <input
        data-test='component-SearchBox'
        type='search'
        id='font-manager-search-box'
        className='wp-filter-search'
        placeholder={GFPDF.fontManagerSearchPlaceHolder}
        value={this.state.searchInput}
        onChange={this.handleSearch}
        onKeyDown={e => e.keyCode === 13 && e.preventDefault()}
        ref={node => (this.input = node)}
        tabIndex='143'
      />
    )
  }
}

/**
 * Map redux state to props
 *
 * @param state: object
 *
 * @returns {{ searchResult: (null || array of object), msg: object }}
 *
 * @since 6.0
 */
const mapStateToProps = state => ({
  searchResult: state.fontManager.searchResult,
  msg: state.fontManager.msg
})

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, { resetSearchResult, searchFontList })(SearchBox)
