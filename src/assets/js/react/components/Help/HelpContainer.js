import React, { Component } from 'react'

class HelpContainer extends Component {

  state = {
    term: '',
    loading: false
  }

  onHandleChange = e => {
    this.setState({ [e.target.name]: e.target.value });
  }

  render() {
    const { term } = this.state;
    return (
      <React.Fragment>
        <input type="text" placeholder="  Search the Gravity PDF Knowledgebase..." id="search-help-input" name="term" value={term} onChange={this.onHandleChange} />
      </React.Fragment>
    )
  }
}


export default HelpContainer