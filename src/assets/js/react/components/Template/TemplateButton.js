import React from 'react'
import PropTypes from 'prop-types'

/**
 * Render the button used to option our Fancy PDF template selector
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
class TemplateButton extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    history: PropTypes.object,
    buttonText: PropTypes.string
  }

  /**
   * When the button is clicked we'll display the `/template` route
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handleClick = (e) => {
    e.preventDefault()
    e.stopPropagation()

    this.props.history.push('/template')
  }

  /**
   * @since 4.1
   */
  render () {
    return (
      <button
        data-test='component-templateButton'
        type='button'
        id='fancy-template-selector'
        className='button gfpdf-button'
        onClick={this.handleClick}
        ref={node => (this.button = node)}
      >
        {this.props.buttonText}
      </button>
    )
  }
}

export default TemplateButton
