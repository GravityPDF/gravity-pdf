import React, { Component } from 'react'
import { connect } from 'react-redux'
import PropTypes from 'prop-types'
import { resetSearchResult, searchFontList } from '../../actions/fontManager'

export class SearchBox extends Component {
  static propTypes = {
    id: PropTypes.string,
    searchResult: PropTypes.oneOfType([
      PropTypes.oneOf([null]).isRequired,
      PropTypes.array.isRequired
    ]),
    msg: PropTypes.object.isRequired,
    resetSearchResult: PropTypes.func.isRequired,
    searchFontList: PropTypes.func.isRequired
  }

  state = {
    searchInput: ''
  }

  componentDidMount () {
    /* add focus to element */
    this.input.focus()
  }

  componentDidUpdate (prevProps, prevState, snapshot) {
    const { id, searchResult, msg } = this.props

    /* */
    if (prevProps.searchResult !== searchResult && !searchResult) {
      this.resetSearchState()
    }

    /* Clear search box after a successful font has been added */
    if (JSON.stringify(prevProps.msg) !== JSON.stringify(msg) && msg.success && id) {
      this.resetSearchState()
    }
  }

  componentWillUnmount () {
    if (this.state.searchInput !== '') {
      this.props.resetSearchResult()
    }
  }

  handleSearch = e => {
    const data = e.target.value

    this.setState({ searchInput: data })
    this.props.searchFontList(data)
  }

  resetSearchState = () => {
    this.setState({ searchInput: '' })
  }

  render () {
    return (
      <input
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

const mapStateToProps = state => ({
  searchResult: state.fontManager.searchResult,
  msg: state.fontManager.msg
})

export default connect(mapStateToProps, { resetSearchResult, searchFontList })(SearchBox)
