import React, { Component } from 'react'
import { connect } from 'react-redux'
import request from 'superagent'
import { updateResult, deleteResult } from '../../actions/help'
import Spinner from '../Spinner'


export const doHandleChange = data => ({
  searchInput: data
});

export const toggleLoadingTrue = () => ({
  loading: true
});

export const toggleLoadingFalse = () => ({
  loading: false
})

export const fetchData = searchInput => {
  // Request API call
  return request.get(`https://gravitypdf.com/wp-json/wp/v2/v5_docs/?search=${searchInput}`);
}


class HelpContainer extends Component {

  // Initialize component state
  state = {
    searchInput: '',
    loading: false
  }

  onHandleChange = e => {
    // Set loading to true
    this.setState(doHandleChange(e.target.value));
    // Set searchInput state value
    this.searchInputLength(e.target.value);
  }

  searchInputLength = data => {
    if (data.length > 3) {
      // Set loading to true
      this.setState(toggleLoadingTrue);
      // Request API call
      fetchData(data)
        // If request is successful
      .then(res => {
        // Pass data into redux action
        this.props.updateResult(res.body)
        this.setState(toggleLoadingFalse)
      })
      // Catch if something went wrong in the call
      .catch(err => {
        console.log('action err -', err)
      });
    } else {
      this.props.deleteResult();
    }
  }

  displayResult = () => {
    const { loading, searchInput } = this.state;
    const { helpResult } = this.props;
    let searchResult;
    let items;

    // Check if search result is not emplty or loading is true
    if (helpResult.length > 0 || loading) {
      // map the search result
      items = (
        helpResult.map((item, index) => (
          <li key={index}>
            <a href={item.link} >{item.title.rendered}</a>
            <div className="except">
              {/* Parse html */}
              <div dangerouslySetInnerHTML={{__html: item.excerpt.rendered}} />
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
        <>
          <h3 className="hndle">
            <span>Gravity PDF Documentation</span>
            {/* { loading ? <span className="spinner is-active"></span> : null  } */}
            { loading ? <div style={{float: 'right'}}><Spinner /></div> : null  }
          </h3>
          <div className="inside rss-widget" style={{display: 'block'}}>
            <ul className="searchParseHTML">
              {items}
            </ul>
          </div>
        </>
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
      <>
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
      </>
    )
  }
}

const mapStateToProps = state => ({
  helpResult: state.help.results
})

export default connect(mapStateToProps, { updateResult, deleteResult })(HelpContainer)