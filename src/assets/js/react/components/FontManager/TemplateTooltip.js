/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'
/* Utilities */
import { adjustFontListHeight } from '../../utilities/FontManager/adjustFontListHeight'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export class TemplateTooltip extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    id: PropTypes.string
  }

  /**
   * Initialize component state
   *
   * @type {{ tooltip: boolean }}
   *
   * @since 6.0
   */
  state = {
    tooltip: false
  }

  /**
   * Toggle state for template usage information box
   *
   * @since 6.0
   */
  handleDisplayInfo = () => {
    this.setState({ tooltip: !this.state.tooltip })

    setTimeout(() => adjustFontListHeight(), 100)
  }

  /**
   * Handle auto highlighting of the information box content once clicked
   *
   * @param e: object
   *
   * @since 6.0
   */
  handleContentHighlight = e => {
    e.target.focus()
    e.target.select()

    document.execCommand('copy')
  }

  /**
   * Display template tooltip UI
   *
   * @since 6.0
   */
  render () {
    const { id } = this.props
    const { tooltip } = this.state

    /* Construct tooltip value */
    const textareaValue = `<style>
.font-${id} {
  font-family: ${id}, sans-serif;
}
</style>
    
<div class="font-${id}">Text</div>`

    return (
      <div data-test='component-TemplateTooltip' className='msg template-usage-link'>
        {tooltip
          ? (
            <span className='dashicons dashicons-arrow-down-alt2' />
            )
          : <span className='dashicons dashicons-arrow-right-alt2' />}
        <a onClick={this.handleDisplayInfo}>
          {GFPDF.fontManagerTemplateTooltipLabel}
        </a>

        {tooltip && (
          <div dangerouslySetInnerHTML={{ __html: sprintf(GFPDF.fontManagerTemplateTooltipDesc, '<a href="https://docs.gravitypdf.com/v6/developers/first-custom-pdf">', '<a href="https://docs.gravitypdf.com/v6/users/setup-pdf#font">', '</a>') }} />
        )}

        {tooltip && (
          <textarea
            id='template_usage_info_box'
            onClick={this.handleContentHighlight}
            onChange={this.handleContentHighlight}
            value={textareaValue}
          />
        )}
      </div>
    )
  }
}

export default TemplateTooltip
