/* Dependencies */
import React from 'react';
/* Components */
import TemplateActivateButton from './TemplateActivateButton';
import TemplateDeleteButton from './TemplateDeleteButton';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Renders the template footer actions that get displayed on the
 * /template/:id pages.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

const TemplateActivateButtonWithRouter = withRouterHooks(
	TemplateActivateButton
);
const TemplateDeleteButtonWithRouter = withRouterHooks(TemplateDeleteButton);

interface Props {
	template: TemplateItem;
	isActiveTemplate?: boolean;
	ajaxUrl?: string;
	ajaxNonce?: string;
	activateText?: string;
	pdfWorkingDirPath?: string;
	templateDeleteText?: string;
	templateConfirmDeleteText?: string;
	templateDeleteErrorText?: string;
}

const TemplateFooterActions = ({
	template,
	isActiveTemplate,
	ajaxUrl,
	ajaxNonce,
	activateText,
	pdfWorkingDirPath,
	templateDeleteText,
	templateConfirmDeleteText,
	templateDeleteErrorText,
}: Props) => {
	const notCoreTemplate = (t: TemplateItem) =>
		String(t.path).indexOf(pdfWorkingDirPath ?? '') !== -1;

	const isCompatible = template.compatible;

	return (
		<div
			data-test="component-templateFooterActions"
			className="theme-actions"
		>
			{!isActiveTemplate && isCompatible ? (
				<TemplateActivateButtonWithRouter
					template={template}
					buttonText={activateText}
				/>
			) : null}

			{!isActiveTemplate && notCoreTemplate(template) ? (
				<TemplateDeleteButtonWithRouter
					template={template}
					ajaxUrl={ajaxUrl}
					ajaxNonce={ajaxNonce}
					buttonText={templateDeleteText}
					templateConfirmDeleteText={templateConfirmDeleteText}
					templateDeleteErrorText={templateDeleteErrorText}
				/>
			) : null}
		</div>
	);
};

export default TemplateFooterActions;
