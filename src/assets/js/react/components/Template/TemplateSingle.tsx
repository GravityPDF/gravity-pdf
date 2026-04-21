/* Dependencies */
import React from 'react';
import { Params } from 'react-router-dom';
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
/* Redux hooks */
import { useAppSelector } from '../../store/hooks';
/* Selectors */
import getTemplates from '../../selectors/getTemplates';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';

/**
 * Renders a single PDF template, which get displayed on the /template/:id page.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

const TemplateHeaderNavigationWithRouter = withRouterHooks(
	TemplateHeaderNavigation
);

interface Props {
	params?: Readonly<Params<string>>;
	showPreviousTemplateText?: string;
	showNextTemplateText?: string;
	ajaxUrl?: string;
	ajaxNonce?: string;
	activateText?: string;
	pdfWorkingDirPath?: string;
	templateDeleteText?: string;
	templateConfirmDeleteText?: string;
	templateDeleteErrorText?: string;
	currentTemplateText?: string;
	versionText?: string;
	groupText?: string;
	tagsText?: string;
}

const TemplateSingle = ({
	params,
	showPreviousTemplateText,
	showNextTemplateText,
	ajaxUrl,
	ajaxNonce,
	activateText,
	pdfWorkingDirPath,
	templateDeleteText,
	templateConfirmDeleteText,
	templateDeleteErrorText,
	currentTemplateText,
	versionText,
	groupText,
	tagsText,
}: Props) => {
	const templates = useAppSelector(getTemplates);
	const activeTemplate = useAppSelector((s) => s.template.activeTemplate);

	const id = params?.id;
	const findCurrentTemplate = (item: { id: string }) => item.id === id;
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
			header={
				<TemplateHeaderNavigationWithRouter
					template={template}
					templateIndex={templateIndex}
					templates={templates}
					showPreviousTemplateText={showPreviousTemplateText}
					showNextTemplateText={showNextTemplateText}
				/>
			}
			footer={
				<TemplateFooterActions
					template={template}
					isActiveTemplate={isCurrentTemplate}
					ajaxUrl={ajaxUrl}
					ajaxNonce={ajaxNonce}
					activateText={activateText}
					pdfWorkingDirPath={pdfWorkingDirPath}
					templateDeleteText={templateDeleteText}
					templateConfirmDeleteText={templateConfirmDeleteText}
					templateDeleteErrorText={templateDeleteErrorText}
				/>
			}
			closeRoute="/template"
		>
			<div
				id="gfpdf-template-detail-view"
				className="gfpdf-template-detail"
			>
				<TemplateScreenshots image={template.screenshot} />
				<div className="theme-info">
					<CurrentTemplate
						isCurrentTemplate={isCurrentTemplate}
						label={currentTemplateText}
					/>
					<Name
						name={template.template}
						version={template.version}
						versionLabel={versionText}
					/>
					<Author
						author={template.author}
						uri={template['author uri'] as string | undefined}
					/>
					<Group group={template.group} label={groupText} />
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
					<Tags tags={template.tags} label={tagsText} />
				</div>
			</div>
		</TemplateContainer>
	);
};

export default TemplateSingle;
