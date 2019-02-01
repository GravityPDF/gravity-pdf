import React, { Component } from 'react'
import { connect } from 'react-redux'
import request from 'superagent'
import { updateResult, deleteResult } from '../../actions/help'

class HelpContainer extends Component {

  // Initialize component state
  state = {
    searchInput: '',
    loading: false
  }

  componentWillReceiveProps(nextProps) {
    // Set loading Spinner to false
    if (nextProps.help) {
      this.setState({ loading: false });
    }
  }

  onHandleChange = e => {
    // Set the current state for initial value
    this.setState({ [e.target.name]: e.target.value });
    // Trigger if input length is greater than 3
    if (e.target.value.length > 3) {
      // Set loading spinner to true
      this.setState({ loading: true });
      // Call function fetchHelpSearch()
      this.fetchHelpSearch(e.target.value);
    } else {
      // Delete/clean old search result
      this.props.deleteResult();
    }
  }

  fetchHelpSearch = (searchInput) => {
    // Request API call
    request
    .get(`https://gravitypdf.com/wp-json/wp/v2/v5_docs/?search=${searchInput}`)
    // If request is successful
    .then(res => {
      // Pass data into redux action
      this.props.updateResult(res.body)
    })
    // Catch if something went wrong in the call
    .catch(err => {
      console.log('action err -', err)
    });
  }

  displayResult = () => {
    const { loading, searchInput } = this.state;
    const { help } = this.props;
    let searchResult;
    let items;

    // Check if search result is not emplty or loading is true
    if (help.length > 0 || loading) {
      // map the search result
      items = (
        help.map((item, index) => (
          <li key={index}>
            <a href={item.link} >{item.title.rendered}</a>
            <div className="except">
              <div>
                {/* Parse html */}
                <div dangerouslySetInnerHTML={{__html: item.excerpt.rendered}} />
              </div>
            </div>
          </li>
        ))
      )
    } else {
      items = (
        <li>It doesn't look like there are any topics related to your issue.</li>
      )
    }
   
    // Check if state searchInput length is greatar than 3 then display items
    if (searchInput.length > 3) {
      searchResult = (
        <React.Fragment>
          <h3 className="hndle">
            <span>Gravity PDF Documentation</span>
            { loading ? <span className="spinner is-active"></span> : null  }
          </h3>
          <div className="inside rss-widget searchParseHTML" style={{display: 'block'}}>
            <ul>
              {items}
            </ul>
          </div>
        </React.Fragment>
      )
    } else {
      searchResult = null;
    }

    // Return searchResult
    return searchResult;
  }

  render() {
    const { searchInput } = this.state;
    return (
      <React.Fragment>
        <input 
          type="text" 
          placeholder="  Search the Gravity PDF Knowledgebase..." 
          id="search-help-input" 
          name="searchInput" 
          value={searchInput} 
          onChange={this.onHandleChange} 
        />
        <div id="search-results">
          <div id="dashboard_primary" className="metabox-holder">
            <div id="documentation-api" className="postbox" style={{display: 'block'}}>
              {/* Call display result */}
              {this.displayResult()}
            </div>
          </div>
        </div>
      </React.Fragment>
    )
  }
}

const mapStateToProps = state => ({
  help: state.help.results
})

export default connect(mapStateToProps, { updateResult, deleteResult })(HelpContainer)