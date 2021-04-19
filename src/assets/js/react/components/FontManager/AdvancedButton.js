/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * AdvancedButton component
 *
 * @since 6.0
 */
export class AdvancedButton extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    history: PropTypes.object
  }

  /**
   * Handle advanced button click and open the font manager modal
   *
   * @param e: object
   *
   * @since 6.0
   */
  handleClick = e => {
    e.preventDefault()

    this.props.history.push('/fontmanager/')
  }

  /**
   * Display advanced button UI
   *
   * @since 6.0
   */
  render () {
    return (
      <button
        data-test='component-AdvancedButton'
        type='button'
        className='button gfpdf-button'
        onClick={this.handleClick}
      >
        {GFPDF.manage}
      </button>
    )
  }
}

export default AdvancedButton
