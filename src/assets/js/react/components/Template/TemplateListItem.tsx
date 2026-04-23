/* Dependencies */
import { __ } from '@wordpress/i18n';
import type { KeyboardEvent } from 'react';
/* Components */
import TemplateScreenshot from './TemplateScreenshot';
import ShowMessage from '../ShowMessage';
import { TemplateDetails, Group } from './TemplateListItemComponents';
import { Name } from './TemplateSingleComponents';
import TemplateActivateButton from './TemplateActivateButton';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import { TEMPLATE_STORE_NAME, templateStore } from '../../store/templateStore';
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

interface Props {
	onSelectTemplate: (id: string) => void;
	onClose: () => void;
	template: TemplateItem;
	activateText?: string;
	templateDetailsText?: string;
}

const TemplateListItem = ({
	onSelectTemplate,
	onClose,
	template,
	activateText,
	templateDetailsText,
}: Props) => {
	const { updateTemplateParam } = useDispatch(TEMPLATE_STORE_NAME);
	const activeTemplate = useSelect(
		(select) => select(templateStore).getActiveTemplate(),
		[]
	);

	const handleShowDetailedTemplate = () => {
		onSelectTemplate(template.id);
	};

	const handleMaybeShowDetailedTemplate = (
		e: KeyboardEvent<HTMLDivElement>
	) => {
		if (
			e.keyCode === 13 &&
			(e.target as HTMLElement).className.indexOf('button') === -1
		) {
			handleShowDetailedTemplate();
		}
	};

	const removeMessage = () => {
		updateTemplateParam(template.id, 'message', null);
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
				template?.group +
				' ' +
				template?.template +
				' ' +
				__('Details', 'gravity-pdf')
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
					<TemplateActivateButton
						onClose={onClose}
						template={template}
						buttonText={activateText}
					/>
				) : null}
			</div>
		</div>
	);
};

export default TemplateListItem;
