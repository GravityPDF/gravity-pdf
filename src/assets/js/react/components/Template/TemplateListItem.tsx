/* Dependencies */
import React from 'react';
import { NavigateFunction } from 'react-router-dom';
/* Components */
import TemplateScreenshot from './TemplateScreenshot';
import ShowMessage from '../ShowMessage';
import { TemplateDetails, Group } from './TemplateListItemComponents';
import { Name } from './TemplateSingleComponents';
import TemplateActivateButton from './TemplateActivateButton';
/* Redux hooks and actions */
import { useAppSelector, useAppDispatch } from '../../store/hooks';
import { updateTemplateParam as updateTemplateParamAction } from '../../actions/templates';
/* Helpers */
import withRouterHooks from '../../utilities/withRouterHooks';
/* Types */
import { TemplateItem } from '../../types';

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

interface Props {
	navigate: NavigateFunction;
	template: TemplateItem;
	activateText?: string;
	templateDetailsText?: string;
}

const TemplateListItem = ({
	navigate,
	template,
	activateText,
	templateDetailsText,
}: Props) => {
	const dispatch = useAppDispatch();
	const activeTemplate = useAppSelector((s) => s.template.activeTemplate);

	const handleShowDetailedTemplate = () => {
		navigate('/template/' + template.id);
	};

	const handleMaybeShowDetailedTemplate = (
		e: React.KeyboardEvent<HTMLDivElement>
	) => {
		if (
			e.keyCode === 13 &&
			(e.target as HTMLElement).className.indexOf('button') === -1
		) {
			handleShowDetailedTemplate();
		}
	};

	const removeMessage = () => {
		dispatch(updateTemplateParamAction(template.id, 'message', null));
	};

	const isActiveTemplate = activeTemplate === template?.id;
	const isCompatible = template?.compatible;
	const activeClass = isActiveTemplate ? 'active theme' : 'theme';

	const message = template?.message as string | undefined;
	const error = template?.error as string | undefined;

	return (
		<div
			data-test="component-templateListItem"
			onClick={handleShowDetailedTemplate}
			onKeyDown={handleMaybeShowDetailedTemplate}
			className={activeClass}
			data-slug={template?.id}
			role="option"
			tabIndex={0}
			aria-label={
				template?.group + ' ' + template?.template + ' ' + GFPDF.details
			}
		>
			<TemplateScreenshot image={template?.screenshot} />

			{error ? <ShowMessage text={error} error /> : null}

			{message ? (
				<ShowMessage
					text={message}
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

export default TemplateListItem;
