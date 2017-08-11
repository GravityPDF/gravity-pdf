import PropTypes from 'prop-types';
import React from 'react'
import { connect } from 'react-redux'
import { selectTemplate } from '../actions/templates'
import { withRouter } from 'react-router-dom'

/**
 * Renders the button used to trigger the current active PDF template
 * On click it triggers our Redux action.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
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
    template: PropTypes.object,
    onTemplateSelect: PropTypes.func,
    buttonText: PropTypes.string,
  };

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
    this.props.onTemplateSelect(this.props.template.get('id'))
  };

  /**
   * @since 4.1
   */
  render() {
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
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{onTemplateSelect: (function(id=string))}}
 *
 * @since 4.1
 */
const mapDispatchToProps = (dispatch) => {
  return {
    onTemplateSelect: (id) => {
      dispatch(selectTemplate(id))
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(null, mapDispatchToProps)(TemplateActivateButton))

