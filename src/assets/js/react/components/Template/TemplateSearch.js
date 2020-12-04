import PropTypes from 'prop-types'
import React from 'react'
import { connect } from 'react-redux'
import debounce from 'lodash.debounce'

import { searchTemplates } from '../../actions/templates'

/**
 * Handles the PDF template search functionality
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
export class TemplateSearch extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    onSearch: PropTypes.func,
    search: PropTypes.string
  }

  /**
   * Debounce our runSearch function so it can only be run once every 200 milliseconds
   *
   * @since 4.1
   */
  componentWillMount () {
    this.runSearch = debounce(this.runSearch, 200)
  }

  /**
   * On mount, add focus to the search box
   *
   * @since 4.1
   */
  componentDidMount () {
    /* add focus to element */
    this.input.focus()
  }

  /**
   * Handles our search event
   *
   * Because ReactJS pools SyntheticEvent and we delay the search with debounce we need
   * to ensure the event is persisted (see https://facebook.github.io/react/docs/events.html#event-pooling)
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handleSearch = (e) => {
    e.persist()
    this.runSearch(e)
  }

  /**
   * Update our Redux store with the search value
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  runSearch = (e) => {
    this.props.onSearch(e.target.value || '')
  }

  /**
   * @since 4.1
   */
  render () {
    return (
      <div>
        <input
          className="wp-filter-search"
          id="wp-filter-search-input"
          ref={node => this.input = node}
          placeholder="Search Installed Templates"
          type="search"
          aria-describedby="live-search-desc"
          tabIndex="145"
          onChange={this.handleSearch}
          defaultValue={this.props.search}
        />
      </div>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 *
 * @returns {{search: string}}
 *
 * @since 4.1
 */
const mapStateToProps = (state) => {
  return {
    search: state.template.search
  }
}

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{onSearch: (function(text=string))}}
 *
 * @since 4.1
 */
const mapDispatchToProps = (dispatch) => {
  return {
    onSearch: (text) => {
      dispatch(searchTemplates(text))
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps, mapDispatchToProps)(TemplateSearch)