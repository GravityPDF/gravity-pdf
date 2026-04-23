/* Dependencies */
import { __ } from '@wordpress/i18n';
/* Components */
import TemplateContainer from './TemplateContainer';
import TemplateListItem from './TemplateListItem';
import TemplateSearch from './TemplateSearch';
import TemplateUploader from './TemplateUploader';
/* Store */
import { useSelect } from '@wordpress/data';
import { templateStore } from '../../store/templateStore';

/**
 * The master component for rendering the all PDF templates as a list
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	onSelectTemplate: (id: string) => void;
	onClose: () => void;
}

const TemplateList = ({ onSelectTemplate, onClose }: Props) => {
	const templates = useSelect(
		(select) => select(templateStore).getFilteredTemplates(),
		[]
	);

	const hasUserPrivs =
		GFPDF.userCapabilities.administrator ||
		GFPDF.userCapabilities.gravityforms_edit_settings ||
		false;

	return (
		<TemplateContainer
			title={__('Installed PDFs', 'gravity-pdf')}
			onClose={onClose}
		>
			<TemplateSearch />
			<div role="listbox">
				{templates?.map((value, index) => {
					return (
						<TemplateListItem
							key={index}
							onSelectTemplate={onSelectTemplate}
							onClose={onClose}
							template={value}
							templateDetailsText={__(
								'Template Details',
								'gravity-pdf'
							)}
							activateText={__('Select', 'gravity-pdf')}
						/>
					);
				})}

				{hasUserPrivs && (
					<TemplateUploader
						addTemplateText={__('Add New Template', 'gravity-pdf')}
						genericUploadErrorText={__(
							'There was a problem with the upload. Reload the page and try again.',
							'gravity-pdf'
						)}
						filenameErrorText={__(
							'Upload is not a valid template. Upload a .zip file.',
							'gravity-pdf'
						)}
						filesizeErrorText={__(
							'Upload exceeds the 10MB limit.',
							'gravity-pdf'
						)}
						installSuccessText={__(
							'Template successfully installed',
							'gravity-pdf'
						)}
						installUpdatedText={__(
							'Template successfully updated',
							'gravity-pdf'
						)}
						templateSuccessfullyInstalledUpdated={__(
							'PDF Template(s) Successfully Installed / Updated',
							'gravity-pdf'
						)}
						templateInstallInstructions={__(
							'If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).',
							'gravity-pdf'
						)}
					/>
				)}
			</div>
		</TemplateContainer>
	);
};

export default TemplateList;
