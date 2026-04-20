/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
/* Components */
import TemplateContainer from './TemplateContainer';
import TemplateListItem from './TemplateListItem';
import TemplateSearch from './TemplateSearch';
import TemplateHeaderTitle from './TemplateHeaderTitle';
import TemplateUploader from './TemplateUploader';
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
 * @param {Object} root0
 * @param {*}      root0.templateHeaderText
 * @param {*}      root0.templateDetailsText
 * @param {*}      root0.activateText
 * @param {*}      root0.ajaxUrl
 * @param {*}      root0.ajaxNonce
 * @param {*}      root0.addTemplateText
 * @param {*}      root0.genericUploadErrorText
 * @param {*}      root0.filenameErrorText
 * @param {*}      root0.filesizeErrorText
 * @param {*}      root0.installSuccessText
 * @param {*}      root0.installUpdatedText
 * @param {*}      root0.templateSuccessfullyInstalledUpdated
 * @param {*}      root0.templateInstallInstructions
 * @since 4.1
 */
const TemplateList = ({
	templateHeaderText,
	templateDetailsText,
	activateText,
	ajaxUrl,
	ajaxNonce,
	addTemplateText,
	genericUploadErrorText,
	filenameErrorText,
	filesizeErrorText,
	installSuccessText,
	installUpdatedText,
	templateSuccessfullyInstalledUpdated,
	templateInstallInstructions,
}) => {
	const templates = useSelector(getTemplates);

	const hasUserPrivs =
		GFPDF.userCapabilities.administrator ||
		GFPDF.userCapabilities.gravityforms_edit_settings ||
		false;

	return (
		<TemplateContainer
			data-test="component-templateList"
			header={
				<TemplateHeaderTitle
					data-test="component-templateHeaderTitle"
					header={templateHeaderText}
				/>
			}
			closeRoute="/"
		>
			<TemplateSearch data-test="component-templateSearch" />
			<div role="listbox">
				{templates?.map((value, index) => {
					return (
						<TemplateListItemWithRouter
							data-test="component-templateListItem"
							key={index}
							template={value}
							templateDetailsText={templateDetailsText}
							activateText={activateText}
						/>
					);
				})}

				{hasUserPrivs && (
					<TemplateUploader
						data-test="component-templateUploader"
						ajaxUrl={ajaxUrl}
						ajaxNonce={ajaxNonce}
						addTemplateText={addTemplateText}
						genericUploadErrorText={genericUploadErrorText}
						filenameErrorText={filenameErrorText}
						filesizeErrorText={filesizeErrorText}
						installSuccessText={installSuccessText}
						installUpdatedText={installUpdatedText}
						templateSuccessfullyInstalledUpdated={
							templateSuccessfullyInstalledUpdated
						}
						templateInstallInstructions={
							templateInstallInstructions
						}
					/>
				)}
			</div>
		</TemplateContainer>
	);
};

TemplateList.propTypes = {
	templateHeaderText: PropTypes.string,
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
	templateInstallInstructions: PropTypes.string,
};

export default TemplateList;
