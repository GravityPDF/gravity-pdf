import React from 'react'
import PropTypes from 'prop-types'

export class AdvancedButton extends React.Component {
  static propTypes = {
    history: PropTypes.object
  }

  handleClick = e => {
    e.preventDefault()

    this.props.history.push('/fontmanager/')
  }

  render () {
    return (
      <button
        data-test='component-AdvancedButton'
        type='button'
        className='button gfpdf-button'
        onClick={this.handleClick}
      >
        Advanced
      </button>
    )
  }
}

export default AdvancedButton
