/* Dependencies */
import { useRef, useEffect, useState } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
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

	const [confirming, setConfirming] = useState(false);

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

	const requestDelete = (e: MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		e.stopPropagation();
		setConfirming(true);
	};

	const confirmDelete = () => {
		if (template?.id) {
			templateProcessing(template.id);
			deleteTemplate(template.id);
		}
		setConfirming(false);
	};

	const cancelDelete = () => setConfirming(false);

	return (
		<>
			<Button
				data-test="component-templateDeleteButton"
				variant="secondary"
				isDestructive
				onClick={requestDelete}
				aria-label={__('Delete Template', 'gravity-pdf')}
				__next40pxDefaultSize={true}
			>
				{__('Delete', 'gravity-pdf')}
			</Button>

			<ConfirmDialog
				isOpen={confirming}
				onConfirm={confirmDelete}
				onCancel={cancelDelete}
			>
				<>
					<p>
						{__(
							'Do you really want to delete this PDF template?',
							'gravity-pdf'
						)}
					</p>
					<p>
						{__(
							"Click 'Cancel' to go back, 'OK' to confirm the delete.",
							'gravity-pdf'
						)}
					</p>
				</>
			</ConfirmDialog>
		</>
	);
};

export default TemplateDeleteButton;
