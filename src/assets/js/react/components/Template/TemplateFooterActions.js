/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
/* Components */
import TemplateActivateButton from './TemplateActivateButton';
import TemplateDeleteButton from './TemplateDeleteButton';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';

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

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.template
 * @param {*}      root0.isActiveTemplate
 * @param {*}      root0.ajaxUrl
 * @param {*}      root0.ajaxNonce
 * @param {*}      root0.activateText
 * @param {*}      root0.pdfWorkingDirPath
 * @param {*}      root0.templateDeleteText
 * @param {*}      root0.templateConfirmDeleteText
 * @param {*}      root0.templateDeleteErrorText
 * @since 4.1
 */
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
}) => {
	const notCoreTemplate = (t) => t.path.indexOf(pdfWorkingDirPath) !== -1;

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

TemplateFooterActions.propTypes = {
	template: PropTypes.object.isRequired,
	isActiveTemplate: PropTypes.bool,
	ajaxUrl: PropTypes.string,
	ajaxNonce: PropTypes.string,
	activateText: PropTypes.string,
	pdfWorkingDirPath: PropTypes.string,
	templateDeleteText: PropTypes.string,
	templateConfirmDeleteText: PropTypes.string,
	templateDeleteErrorText: PropTypes.string,
};

export default TemplateFooterActions;
