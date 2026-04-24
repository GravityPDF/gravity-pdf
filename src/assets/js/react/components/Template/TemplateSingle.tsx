/* Dependencies */
import { __ } from '@wordpress/i18n';
/* Components */
import TemplateContainer from './TemplateContainer';
import TemplateHeaderNavigation from './TemplateHeaderNavigation';
import TemplateFooterActions from './TemplateFooterActions';
import TemplateScreenshot from './TemplateScreenshot';
import ShowMessage from '../ShowMessage';
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
	const authorUri = template['author uri'] as string | undefined;

	return (
		<TemplateContainer
			title={template.template}
			header={
				<TemplateHeaderNavigation
					template={template}
					templateIndex={templateIndex}
					templates={templates}
					onSelectTemplate={onSelectTemplate}
				/>
			}
			footer={
				<TemplateFooterActions
					template={template}
					onSelectTemplate={onSelectTemplate}
					onClose={onClose}
					isActiveTemplate={isCurrentTemplate}
					pdfWorkingDirPath={GFPDF.pdfWorkingDir}
				/>
			}
			onClose={() => onSelectTemplate('')}
		>
			<div
				id="gfpdf-template-detail-view"
				className="gfpdf-template-detail"
			>
				<TemplateScreenshot image={template.screenshot} wrapped />
				<div className="theme-info">
					{isCurrentTemplate ? (
						<span
							data-test="component-currentTemplate"
							className="current-label"
						>
							{__('Current Template', 'gravity-pdf')}
						</span>
					) : null}

					<h2 data-test="component-name" className="theme-name">
						{template.template}
						{template.version ? (
							<span
								data-test="component-version"
								className="theme-version"
							>
								{__('Version', 'gravity-pdf')}:{' '}
								{template.version}
							</span>
						) : null}
					</h2>

					{authorUri ? (
						<p
							data-test="component-author"
							className="theme-author"
						>
							<a href={authorUri}>{template.author}</a>
						</p>
					) : (
						<p
							data-test="component-author"
							className="theme-author"
						>
							{template.author}
						</p>
					)}

					<p data-test="component-group" className="theme-author">
						<strong>
							{__('Group', 'gravity-pdf')}: {template.group}
						</strong>
					</p>

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

					<p
						data-test="component-description"
						className="theme-description"
					>
						{template.description}
					</p>

					{template.tags ? (
						<p data-test="component-tags" className="theme-tags">
							<span>{__('Tags', 'gravity-pdf')}:</span>{' '}
							{template.tags}
						</p>
					) : null}
				</div>
			</div>
		</TemplateContainer>
	);
};

export default TemplateSingle;
