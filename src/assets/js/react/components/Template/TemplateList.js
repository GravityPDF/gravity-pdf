import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import getTemplates from '../../selectors/getTemplates'
import TemplateContainer from './TemplateContainer'
import TemplateListItem from './TemplateListItem'
import TemplateSearch from './TemplateSearch'
import TemplateHeaderTitle from './TemplateHeaderTitle'
import TemplateUploader from './TemplateUploader'

/**
 * The master component for rendering the all PDF templates as a list
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
export class TemplateList extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    templateHeaderText: PropTypes.string,
    templates: PropTypes.array,
    templateDetailsText: PropTypes.string,
    activateText: PropTypes.string,
    ajaxUrl: PropTypes.string,
    ajaxNonce: PropTypes.string,
    addTemplateText: PropTypes.string,
    genericUploadErrorText: PropTypes.string,
    filenameErrorText: PropTypes.string,
    filesizeErrorText: PropTypes.string,
    installSuccessText: PropTypes.string,
    installUpdatedText: PropTypes.string,
    templateSuccessfullyInstalledUpdated: PropTypes.string,
    templateInstallInstructions: PropTypes.string
  }

  /**
   * @since 4.1
   */
  render () {
    const header = <TemplateHeaderTitle header={this.props.templateHeaderText} />
    const hasUserPrivs = GFPDF.userCapabilities.administrator || GFPDF.userCapabilities.gravityforms_edit_settings || false

    return (
      <TemplateContainer header={header} closeRoute="/">
        <TemplateSearch />
        <div>
          {
            this.props.templates.map((value, index) => {
              return <TemplateListItem
                key={index}
                template={value}
                templateDetailsText={this.props.templateDetailsText}
                activateText={this.props.activateText} />
            })
          }

          {
            hasUserPrivs &&
            <TemplateUploader
              ajaxUrl={this.props.ajaxUrl}
              ajaxNonce={this.props.ajaxNonce}
              addTemplateText={this.props.addTemplateText}
              genericUploadErrorText={this.props.genericUploadErrorText}
              filenameErrorText={this.props.filenameErrorText}
              filesizeErrorText={this.props.filesizeErrorText}
              installSuccessText={this.props.installSuccessText}
              installUpdatedText={this.props.installUpdatedText}
              templateSuccessfullyInstalledUpdated={this.props.templateSuccessfullyInstalledUpdated}
              templateInstallInstructions={this.props.templateInstallInstructions}
            />
          }

        </div>
      </TemplateContainer>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 *
 * @returns {{templates}}
 *
 * @since 4.1
 */
const mapStateToProps = (state) => {
  return {
    templates: getTemplates(state)
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps)(TemplateList)
