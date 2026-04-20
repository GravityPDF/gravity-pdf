/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
/* Components */
import TemplateScreenshot from './TemplateScreenshot';
import ShowMessage from '../ShowMessage';
import { TemplateDetails, Group } from './TemplateListItemComponents';
import { Name } from './TemplateSingleComponents';
import TemplateActivateButton from './TemplateActivateButton';
/* Redux actions */
import { updateTemplateParam as updateTemplateParamAction } from '../../actions/templates';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';

/**
 * Display the individual template item for usage our template list
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

const TemplateActivateButtonWithRouter = withRouterHooks(
	TemplateActivateButton
);

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.navigate
 * @param {*}      root0.template
 * @param {*}      root0.activateText
 * @param {*}      root0.templateDetailsText
 * @since 4.1
 */
const TemplateListItem = ({
	navigate,
	template,
	activateText,
	templateDetailsText,
}) => {
	const dispatch = useDispatch();
	const activeTemplate = useSelector((s) => s.template.activeTemplate);

	const handleShowDetailedTemplate = () => {
		navigate('/template/' + template.id);
	};

	const handleMaybeShowDetailedTemplate = (e) => {
		if (e.keyCode === 13 && e.target.className.indexOf('button') === -1) {
			handleShowDetailedTemplate();
		}
	};

	const removeMessage = () => {
		dispatch(updateTemplateParamAction(template.id, 'message', null));
	};

	const isActiveTemplate = activeTemplate === template?.id;
	const isCompatible = template?.compatible;
	const activeClass = isActiveTemplate ? 'active theme' : 'theme';

	return (
		<div
			data-test="component-templateListItem"
			onClick={handleShowDetailedTemplate}
			onKeyDown={handleMaybeShowDetailedTemplate}
			className={activeClass}
			data-slug={template?.id}
			role="option"
			tabIndex="0"
			aria-label={
				template?.group + ' ' + template?.template + ' ' + GFPDF.details
			}
		>
			<TemplateScreenshot image={template?.screenshot} />

			{template?.error ? (
				<ShowMessage text={template.error} error />
			) : null}

			{template?.message ? (
				<ShowMessage
					text={template.message}
					dismissableCallback={removeMessage}
					dismissable
					delay={12000}
				/>
			) : null}

			<TemplateDetails label={templateDetailsText} />
			<Group group={template?.group} />
			<Name name={template?.template} />

			<div className="theme-actions">
				{!isActiveTemplate && isCompatible ? (
					<TemplateActivateButtonWithRouter
						template={template}
						buttonText={activateText}
					/>
				) : null}
			</div>
		</div>
	);
};

TemplateListItem.propTypes = {
	navigate: PropTypes.func,
	template: PropTypes.object,
	activeTemplate: PropTypes.string,
	activateText: PropTypes.string,
	templateDetailsText: PropTypes.string,
};

export default TemplateListItem;
