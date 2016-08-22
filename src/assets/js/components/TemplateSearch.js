import React from 'react'
import { connect } from 'react-redux'
import debounce from 'lodash.debounce'
import { searchTemplates } from '../actions/templates'

export const TemplateSearch = React.createClass({

  propTypes: {
    onSearch: React.PropTypes.func.isRequired,
    search: React.PropTypes.string
  },

  componentWillMount() {
    this.runSearch = debounce(this.runSearch, 200)
  },

  componentDidMount() {
    /* add focus to element */
    this.input.focus()
  },

  handleSearch(e) {
    e.persist()
    this.runSearch(e)
  },

  runSearch(e) {
    this.props.onSearch(e.target.value || '')
  },

  render() {
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
})

const mapStateToProps = (state) => {
  return {
    search: state.template.search
  }
}

const mapDispatchToProps = (dispatch) => {
  return {
    onSearch: (text) => {
      dispatch(searchTemplates(text))
    }
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(TemplateSearch)