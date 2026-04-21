/* Dependencies */
import React from 'react';
/* Components */
import TemplateContainer from './TemplateContainer';
import TemplateListItem from './TemplateListItem';
import TemplateSearch from './TemplateSearch';
import TemplateHeaderTitle from './TemplateHeaderTitle';
import TemplateUploader from './TemplateUploader';
/* Redux hooks */
import { useAppSelector } from '../../store/hooks';
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

interface Props {
	templateHeaderText?: string;
	templateDetailsText?: string;
	activateText?: string;
	ajaxUrl?: string;
	ajaxNonce?: string;
	addTemplateText?: string;
	genericUploadErrorText?: string;
	filenameErrorText?: string;
	filesizeErrorText?: string;
	installSuccessText?: string;
	installUpdatedText?: string;
	templateSuccessfullyInstalledUpdated?: string;
	templateInstallInstructions?: string;
}

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
}: Props) => {
	const templates = useAppSelector(getTemplates);

	const hasUserPrivs =
		GFPDF.userCapabilities.administrator ||
		GFPDF.userCapabilities.gravityforms_edit_settings ||
		false;

	return (
		<TemplateContainer
			header={<TemplateHeaderTitle header={templateHeaderText} />}
			closeRoute="/"
		>
			<TemplateSearch />
			<div role="listbox">
				{templates?.map((value, index) => {
					return (
						<TemplateListItemWithRouter
							key={index}
							template={value}
							templateDetailsText={templateDetailsText}
							activateText={activateText}
						/>
					);
				})}

				{hasUserPrivs && (
					<TemplateUploader
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

export default TemplateList;
