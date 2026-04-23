/* Dependencies */
import { __, sprintf } from '@wordpress/i18n';
/* Components */
import TemplateContainer from './TemplateContainer';
import TemplateHeaderNavigation from './TemplateHeaderNavigation';
import TemplateFooterActions from './TemplateFooterActions';
import TemplateScreenshots from './TemplateScreenshots';
import ShowMessage from '../ShowMessage';
import {
	CurrentTemplate,
	Name,
	Author,
	Group,
	Description,
	Tags,
} from './TemplateSingleComponents';
/* Store */
import { useSelect } from '@wordpress/data';
import { templateStore } from '../../store/templateStore';

/**
 * Renders a single PDF template, displayed in the detail view.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	activeTemplateId: string;
	onSelectTemplate: (id: string) => void;
	onClose: () => void;
}

const TemplateSingle = ({
	activeTemplateId,
	onSelectTemplate,
	onClose,
}: Props) => {
	const templates = useSelect(
		(select) => select(templateStore).getFilteredTemplates(),
		[]
	);
	const activeTemplate = useSelect(
		(select) => select(templateStore).getActiveTemplate(),
		[]
	);

	const findCurrentTemplate = (item: { id: string }) =>
		item.id === activeTemplateId;
	const template = templates?.find(findCurrentTemplate);
	const templateIndex = templates?.findIndex(findCurrentTemplate);

	/* Prevent rendering when a template isn't found (race condition on delete) */
	if (!template) {
		return null;
	}

	const isCurrentTemplate = activeTemplate === template.id;
	const longMessage = template.long_message as string | undefined;
	const longError = template.long_error as string | undefined;

	return (
		<TemplateContainer
			title={template.template}
			header={
				<TemplateHeaderNavigation
					template={template}
					templateIndex={templateIndex}
					templates={templates}
					onSelectTemplate={onSelectTemplate}
					showPreviousTemplateText={__(
						'Show previous template',
						'gravity-pdf'
					)}
					showNextTemplateText={__(
						'Show next template',
						'gravity-pdf'
					)}
				/>
			}
			footer={
				<TemplateFooterActions
					template={template}
					onSelectTemplate={onSelectTemplate}
					onClose={onClose}
					isActiveTemplate={isCurrentTemplate}
					activateText={__('Select', 'gravity-pdf')}
					pdfWorkingDirPath={GFPDF.pdfWorkingDir}
					templateDeleteText={__('Delete', 'gravity-pdf')}
					templateConfirmDeleteText={sprintf(
						/* translators: %s is replaced with a double newline */
						__(
							"Do you really want to delete this PDF template?%sClick 'Cancel' to go back, 'OK' to confirm the delete.",
							'gravity-pdf'
						),
						'\n\n'
					)}
					templateDeleteErrorText={__(
						'Could not delete template.',
						'gravity-pdf'
					)}
				/>
			}
			onClose={() => onSelectTemplate('')}
		>
			<div
				id="gfpdf-template-detail-view"
				className="gfpdf-template-detail"
			>
				<TemplateScreenshots image={template.screenshot} />
				<div className="theme-info">
					<CurrentTemplate
						isCurrentTemplate={isCurrentTemplate}
						label={__('Current Template', 'gravity-pdf')}
					/>
					<Name
						name={template.template}
						version={template.version}
						versionLabel={__('Version', 'gravity-pdf')}
					/>
					<Author
						author={template.author}
						uri={template['author uri'] as string | undefined}
					/>
					<Group
						group={template.group}
						label={__('Group', 'gravity-pdf')}
					/>
					{longMessage ? (
						<ShowMessage
							data-test="component-showMessageLong_message"
							text={longMessage}
						/>
					) : null}
					{longError ? (
						<ShowMessage
							data-test="component-showMessageLong_error"
							text={longError}
							error
						/>
					) : null}
					<Description desc={template.description} />
					<Tags
						tags={template.tags}
						label={__('Tags', 'gravity-pdf')}
					/>
				</div>
			</div>
		</TemplateContainer>
	);
};

export default TemplateSingle;
