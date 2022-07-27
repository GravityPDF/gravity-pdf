import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { selectTemplate } from '../../actions/templates'
import { withRouter } from 'react-router-dom'

/**
 * Renders the button used to trigger the current active PDF template
 * On click it triggers our Redux action.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateActivateButton extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    history: PropTypes.object,
    onTemplateSelect: PropTypes.func,
    template: PropTypes.object,
    buttonText: PropTypes.string
  }

  /**
   * Update our route and trigger a Redux action to select the current template
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  selectTemplate = (e) => {
    e.preventDefault()
    e.stopPropagation()

    this.props.history.push('')
    this.props.onTemplateSelect(this.props.template.id)
  }

  /**
   * @since 4.1
   */
  render () {
    return (
      <a
        onClick={this.selectTemplate}
        href="#"
        tabIndex="150"
        className="button button-primary activate">
        {this.props.buttonText}
      </a>
    )
  }
}

/**
 * TemplateActivateButton
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{onTemplateSelect: (function(id=string))}}
 *
 * @since 4.1
 */
const mapDispatchToProps = dispatch => {
  return {
    onTemplateSelect: id => dispatch(selectTemplate(id))
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(null, mapDispatchToProps)(TemplateActivateButton))
