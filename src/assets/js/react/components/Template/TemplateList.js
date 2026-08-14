/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Components */
import TemplateContainer from './TemplateContainer';
import TemplateListItem from './TemplateListItem';
import TemplateSearch from './TemplateSearch';
import TemplateHeaderTitle from './TemplateHeaderTitle';
import TemplateUploader from './TemplateUploader';
import TemplateUploaderTile from './TemplateUploaderTile';
/* Selectors */
import getTemplates from '../../selectors/getTemplates';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';

/**
 * The master component for rendering the all PDF templates as a list
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/* Temp fix: Create a HoC to support new react router */
const TemplateListItemWithRouter = withRouterHooks(TemplateListItem);

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateList extends Component {
	/**
	 * @since 4.1
	 */
	static propTypes = {
		templateHeaderText: PropTypes.string,
		templates: PropTypes.array,
		templateDetailsText: PropTypes.string,
		activateText: PropTypes.string,
		addTemplateText: PropTypes.string,
		genericUploadErrorText: PropTypes.string,
		filenameErrorText: PropTypes.string,
		filesizeErrorText: PropTypes.string,
		installSuccessText: PropTypes.string,
		installUpdatedText: PropTypes.string,
		templateSuccessfullyInstalledUpdated: PropTypes.string,
		templateInstallInstructions: PropTypes.string,
		dropzoneText: PropTypes.string,
		uploadInProgressText: PropTypes.string,
		maxFileSize: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
	};

	/**
	 * @since 4.1
	 */
	render() {
		const hasUserPrivs =
			GFPDF.userCapabilities.administrator ||
			GFPDF.userCapabilities.gravityforms_edit_settings ||
			false;

		const templateManager = (
			<TemplateContainer
				data-test="component-templateList"
				header={
					<TemplateHeaderTitle
						data-test="component-templateHeaderTitle"
						header={this.props.templateHeaderText}
					/>
				}
				closeRoute="/"
			>
				<TemplateSearch data-test="component-templateSearch" />
				<div role="listbox">
					{this.props.templates?.map((value, index) => {
						return (
							<TemplateListItemWithRouter
								data-test="component-templateListItem"
								key={index}
								template={value}
								templateDetailsText={
									this.props.templateDetailsText
								}
								activateText={this.props.activateText}
							/>
						);
					})}

					{hasUserPrivs && (
						<TemplateUploaderTile data-test="component-templateUploaderTile" />
					)}
				</div>
			</TemplateContainer>
		);

		if (!hasUserPrivs) {
			return templateManager;
		}

		/* Wrap the whole Template Manager so a zip can be dropped anywhere in the window */
		return (
			<TemplateUploader
				data-test="component-templateUploader"
				addTemplateText={this.props.addTemplateText}
				genericUploadErrorText={this.props.genericUploadErrorText}
				filenameErrorText={this.props.filenameErrorText}
				filesizeErrorText={this.props.filesizeErrorText}
				installSuccessText={this.props.installSuccessText}
				installUpdatedText={this.props.installUpdatedText}
				templateSuccessfullyInstalledUpdated={
					this.props.templateSuccessfullyInstalledUpdated
				}
				templateInstallInstructions={
					this.props.templateInstallInstructions
				}
				dropzoneText={this.props.dropzoneText}
				uploadInProgressText={this.props.uploadInProgressText}
				maxFileSize={this.props.maxFileSize}
			>
				{templateManager}
			</TemplateUploader>
		);
	}
}

/**
 * Map state to props
 *
 * @param { Readonly<Object> } state The current Redux State
 *
 * @return {{ templates }} mapped state
 *
 * @since 4.1
 */
const mapStateToProps = (state) => {
	return {
		templates: getTemplates(state),
	};
};

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps)(TemplateList);
