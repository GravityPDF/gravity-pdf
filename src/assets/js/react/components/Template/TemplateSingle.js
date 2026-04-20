/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
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

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.params
 * @param {*}      root0.showPreviousTemplateText
 * @param {*}      root0.showNextTemplateText
 * @param {*}      root0.ajaxUrl
 * @param {*}      root0.ajaxNonce
 * @param {*}      root0.activateText
 * @param {*}      root0.pdfWorkingDirPath
 * @param {*}      root0.templateDeleteText
 * @param {*}      root0.templateConfirmDeleteText
 * @param {*}      root0.templateDeleteErrorText
 * @param {*}      root0.currentTemplateText
 * @param {*}      root0.versionText
 * @param {*}      root0.groupText
 * @param {*}      root0.tagsText
 * @since 4.1
 */
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
}) => {
	const templates = useSelector(getTemplates);
	const activeTemplate = useSelector((s) => s.template.activeTemplate);

	const id = params?.id;
	const findCurrentTemplate = (item) => item.id === id;
	const template = templates?.find(findCurrentTemplate);
	const templateIndex = templates?.findIndex(findCurrentTemplate);

	/* Prevent rendering when a template isn't found (race condition on delete) */
	if (!template) {
		return null;
	}

	const isCurrentTemplate = activeTemplate === template.id;

	return (
		<TemplateContainer
			data-test="component-templateSingle"
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
						uri={template['author uri']}
					/>
					<Group group={template.group} label={groupText} />
					{template.long_message ? (
						<ShowMessage
							data-test="component-showMessageLong_message"
							text={template.long_message}
						/>
					) : null}
					{template.long_error ? (
						<ShowMessage
							data-test="component-showMessageLong_error"
							text={template.long_error}
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

TemplateSingle.propTypes = {
	params: PropTypes.object,
	showPreviousTemplateText: PropTypes.string,
	showNextTemplateText: PropTypes.string,
	ajaxUrl: PropTypes.string,
	ajaxNonce: PropTypes.string,
	activateText: PropTypes.string,
	pdfWorkingDirPath: PropTypes.string,
	templateDeleteText: PropTypes.string,
	templateConfirmDeleteText: PropTypes.string,
	templateDeleteErrorText: PropTypes.string,
	currentTemplateText: PropTypes.string,
	versionText: PropTypes.string,
	groupText: PropTypes.string,
	tagsText: PropTypes.string,
};

export default TemplateSingle;
