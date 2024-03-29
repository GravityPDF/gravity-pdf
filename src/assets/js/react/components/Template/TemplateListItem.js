/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { withRouter } from 'react-router-dom'
/* Components */
import TemplateScreenshot from './TemplateScreenshot'
import ShowMessage from '../ShowMessage'
import { TemplateDetails, Group } from './TemplateListItemComponents'
import { Name } from './TemplateSingleComponents'
import TemplateActivateButton from './TemplateActivateButton'
/* Redux actions */
import { updateTemplateParam } from '../../actions/templates'

/**
 * Display the individual template item for usage our template list
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateListItem extends Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    history: PropTypes.object,
    template: PropTypes.object,
    activeTemplate: PropTypes.string,
    updateTemplateParam: PropTypes.func,
    activateText: PropTypes.string,
    templateDetailsText: PropTypes.string
  }

  /**
   * Check if the Enter key is pressed and not focused on a button
   * then display the template details page
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handleMaybeShowDetailedTemplate = (e) => {
    /* Show detailed template when the Enter key is pressed and the active element doesn't include a 'button' class */
    if (e.keyCode === 13 && (e.target.className.indexOf('button') === -1)) {
      this.handleShowDetailedTemplate()
    }
  }

  /**
   * Update the URL to show the PDF template details page
   *
   * @since 4.1
   */
  handleShowDetailedTemplate = () => {
    this.props.history.push('/template/' + this.props.template.id)
  }

  /**
   * Call Redux action to remove any stored messages for this template
   *
   * @since 4.1
   */
  removeMessage = () => {
    this.props.updateTemplateParam(this.props.template.id, 'message', null)
  }

  /**
   * @since 4.1
   */
  render () {
    const item = this.props.template
    const isActiveTemplate = this.props.activeTemplate === item.id
    const isCompatible = item.compatible
    const activeTemplate = (isActiveTemplate) ? 'active theme' : 'theme'

    return (
      <div
        data-test='component-templateListItem'
        onClick={this.handleShowDetailedTemplate}
        onKeyDown={this.handleMaybeShowDetailedTemplate}
        className={activeTemplate}
        data-slug={item.id}
        tabIndex='150'
        role='option'
        aria-label={item.group + ' ' + item.template + ' ' + GFPDF.details}
      >

        <TemplateScreenshot
          data-test='component-templateScreenshot'
          image={item.screenshot}
        />
        {item.error
          ? (
            <ShowMessage
              data-test='component-showMessage'
              text={item.error}
              error
            />
            )
          : null}
        {item.message
          ? (
            <ShowMessage
              data-test='component-showMessage'
              text={item.message}
              dismissableCallback={this.removeMessage}
              dismissable
              delay={12000}
            />
            )
          : null}

        <TemplateDetails
          data-test='component-templateDetails'
          label={this.props.templateDetailsText}
        />
        <Group data-test='component-group' group={item.group} />
        <Name data-test='component-name' name={item.template} />

        <div className='theme-actions'>
          {!isActiveTemplate && isCompatible
            ? (
              <TemplateActivateButton
                data-test='component-templateActivateButton'
                template={this.props.template}
                buttonText={this.props.activateText}
              />
              )
            : null}
        </div>
      </div>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 *
 * @returns {{activeTemplate: string}}
 *
 * @since 4.1
 */
const mapStateToProps = (state) => {
  return {
    activeTemplate: state.template.activeTemplate
  }
}

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{updateTemplateParam: (function(id=string, name=string, value=string))}}
 *
 * @since 4.1
 */
export const mapDispatchToProps = (dispatch) => {
  return {
    updateTemplateParam: (id, name, value) => {
      dispatch(updateTemplateParam(id, name, value))
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default withRouter(connect(mapStateToProps, mapDispatchToProps)(TemplateListItem))
