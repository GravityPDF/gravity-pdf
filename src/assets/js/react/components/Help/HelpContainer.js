import React, { Component } from 'react'
import { connect } from 'react-redux'
import request from 'superagent'
import debounce from 'lodash.debounce'
import { updateResult, deleteResult } from '../../actions/help'
import DisplayResult from './DisplayResult'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
 */

/**
 * Handles the grunt work for our Help Page Search Input (API calls, display, state ect)
 *
 * @since 5.2
 */
export class HelpContainer extends Component {

  /**
   * Initialize component state
   *
   * @type {{searchInput: string, loading: boolean}}
   *
   * @since 5.2
   */
  constructor (props) {
    super(props)
    this.state = {
      searchInput: '',
      loading: false
    }

    this.searchInputLength = debounce(this.searchInputLength, 400)
  }

  /**
   * Handle onChange Event for the Search Input
   *
   * @param event
   *
   * @since 5.2
   */
  onHandleChange = event => {
    // Set loading to true
    this.setState({ searchInput: event.target.value })
    // Set searchInput state value
    this.searchInputLength(event.target.value)
  }

  /**
   * Check for Search Input length and pass to Redux Action
   *
   * @since 5.2
   */
  searchInputLength = async data => {
    if (data.length > 3) {
      /* Set loading to true */
      this.setState({ loading: true })
      /* Request API call */
      let result = await this.fetchData(data)
      /* Pass data into redux action */
      this.props.updateResult(result)
      /* Set loading to false */
      this.setState({ loading: false })
    } else {
      /* Call deleteResult into Redux Action */
      this.props.deleteResult()
    }
  }

  /**
   * Do AJAX call
   *
   * @param searchInput
   *
   * @since 5.2
   */
  fetchData = async (searchInput) => {
    let res = await request.get(`https://gravitypdf.com/wp-json/wp/v2/v5_docs/?search=${searchInput}`)
    let data = await res.body
    return data
  }

  /**
   * Renders Search Input and DisplayResult Component UI
   *
   * @since 5.2
   */
  render () {
    const { searchInput } = this.state
    return (
      <>
        <input
          type="text"
          placeholder={'  ' + GFPDF.searchPlaceholder}
          id="search-help-input"
          name="searchInput"
          value={searchInput}
          onChange={this.onHandleChange}
        />
        <DisplayResult
          searchInput={this.state.searchInput}
          loading={this.state.loading}
          helpResult={this.props.helpResult}
        />
      </>
    )
  }
}

/**
 * Map Redux state to props
 *
 * @param state
 * @returns {{helpResult: (object)}}
 *
 * @since 5.2
 */
const mapStateToProps = state => ({
  helpResult: state.help.results
})

/**
 * Dispatch Redux actions as props
 *
 * @returns {{updateResult: (object), deleteResult}}
 *
 * @since 5.2
 */
export default connect(mapStateToProps, { updateResult, deleteResult })(HelpContainer)
