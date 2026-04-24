/* Dependencies */
import { useRef, useEffect } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import { TEMPLATE_STORE_NAME, templateStore } from '../../store/templateStore';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Renders a delete button which then queries our server and
 * removes the selected PDF template
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	onSelectTemplate: (id: string) => void;
	template?: TemplateItem;
}

const TemplateDeleteButton = ({ onSelectTemplate, template }: Props) => {
	const {
		addTemplate,
		deleteTemplate,
		templateProcessing,
		clearTemplateProcessing,
	} = useDispatch(TEMPLATE_STORE_NAME);
	const getTemplateProcessing = useSelect(
		(select) => select(templateStore).getTemplateProcessing(),
		[]
	);

	/* Track previous value to replicate componentDidUpdate comparisons */
	const prevGetTemplateProcessingRef = useRef(getTemplateProcessing);

	/* componentDidUpdate: navigate/recover when templateProcessing changes */
	useEffect(() => {
		const prev = prevGetTemplateProcessingRef.current;
		prevGetTemplateProcessingRef.current = getTemplateProcessing;

		if (prev === getTemplateProcessing) {
			return;
		}

		if (getTemplateProcessing === 'success') {
			onSelectTemplate('');
		}

		if (getTemplateProcessing === 'failed') {
			addTemplate({
				...template,
				error: __('Could not delete template.', 'gravity-pdf'),
			} as TemplateItem);
			onSelectTemplate('');
			clearTemplateProcessing();
		}
	}, [
		getTemplateProcessing,
		onSelectTemplate,
		addTemplate,
		clearTemplateProcessing,
		template,
	]);

	const handleDeleteTemplate = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();

		const confirmMessage = sprintf(
			/* translators: %s is replaced with a double newline */
			__(
				"Do you really want to delete this PDF template?%sClick 'Cancel' to go back, 'OK' to confirm the delete.",
				'gravity-pdf'
			),
			'\n\n'
		);

		if (window.confirm(confirmMessage)) {
			if (template?.id) {
				templateProcessing(template.id);
				deleteTemplate(template.id);
			}
		}
	};

	return (
		<Button
			data-test="component-templateDeleteButton"
			variant="secondary"
			isDestructive
			onClick={handleDeleteTemplate}
			aria-label={__('Delete Template', 'gravity-pdf')}
			__next40pxDefaultSize={true}
		>
			{__('Delete', 'gravity-pdf')}
		</Button>
	);
};

export default TemplateDeleteButton;
