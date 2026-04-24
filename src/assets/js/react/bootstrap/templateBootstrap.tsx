/* Dependencies */
import { useState, lazy, Suspense, createRoot } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { subscribe, select, dispatch, useSelect } from '@wordpress/data';
/* Store */
import { TEMPLATE_STORE_NAME, templateStore } from '../store/templateStore';

const TemplateList = lazy(() => import('../components/Template/TemplateList'));
const TemplateSingle = lazy(
	() => import('../components/Template/TemplateSingle')
);

/**
 * Advanced Template Selector Bootstrap
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

const TemplateApp = () => {
	/* Auto-open when navigated here from WP backend via hash URL */
	const [isOpen, setIsOpen] = useState(() =>
		window.location.hash.startsWith('#/template')
	);
	const [activeTemplateId, setActiveTemplateId] = useState('');

	/* Resolve the active template's display name for the Modal title */
	const activeTemplateName = useSelect(
		(s) => {
			if (!activeTemplateId) {
				return null;
			}
			const match = (
				s(templateStore).getFilteredTemplates() as
					| { id: string; template?: string }[]
					| null
			)?.find((t) => t.id === activeTemplateId);
			return match?.template ?? null;
		},
		[activeTemplateId]
	);

	const handleCloseModal = () => {
		setIsOpen(false);
		setActiveTemplateId('');
	};

	/* Back-from-detail (if viewing a template) OR close the modal (if on the list) */
	const handleRequestClose = () => {
		if (activeTemplateId) {
			setActiveTemplateId('');
			return;
		}
		handleCloseModal();
	};

	const title = activeTemplateName ?? __('Installed PDFs', 'gravity-pdf');

	return (
		<>
			<Button
				data-test="component-templateButton"
				variant="secondary"
				onClick={(e: MouseEvent<HTMLButtonElement>) => {
					/* Parent Gravity Forms row has a click listener we don't want to fire */
					e.stopPropagation();
					setIsOpen(true);
				}}
				aria-label={__('Manage PDF Templates', 'gravity-pdf')}
				__next40pxDefaultSize={true}
			>
				{__('Manage', 'gravity-pdf')}
			</Button>

			{isOpen && (
				<Modal
					title={title}
					onRequestClose={handleRequestClose}
					className="gfpdf-template-manager-modal"
					size="fill"
				>
					<div
						data-test="component-templateContainer"
						className="gfpdf-template-manager-body"
					>
						<Suspense fallback={<div />}>
							{activeTemplateId ? (
								<TemplateSingle
									activeTemplateId={activeTemplateId}
									onSelectTemplate={setActiveTemplateId}
									onClose={handleCloseModal}
								/>
							) : (
								<TemplateList
									onSelectTemplate={setActiveTemplateId}
									onClose={handleCloseModal}
								/>
							)}
						</Suspense>
					</div>
				</Modal>
			)}
		</>
	);
};

/**
 * Handles the loading of the Fancy Template Selector.
 *
 * @param templateField
 * @since 4.1
 */
export function templateBootstrap(templateField: HTMLSelectElement): void {
	const mountPoint = createTemplateMarkup(templateField);
	createRoot(mountPoint).render(<TemplateApp />);

	/*
	 * Listen for @wordpress/data store updates and do DOM updates
	 */
	activeTemplateStoreListener(templateField);
	templateChangeStoreListener(templateField);
}

/**
 * Wrap the template <select> in a flex container and append a <span> mount
 * point for the React root.
 *
 * @param templateField
 * @since 4.1
 */
export function createTemplateMarkup(
	templateField: HTMLSelectElement
): HTMLSpanElement {
	const wrapper = document.createElement('div');
	wrapper.id = 'gfpdf-settings-field-wrapper-template-container';
	templateField.parentNode!.insertBefore(wrapper, templateField);
	wrapper.appendChild(templateField);

	const mountPoint = document.createElement('span');
	mountPoint.id = 'gpdf-advance-template-selector';
	wrapper.appendChild(mountPoint);

	return mountPoint;
}

/**
 * Listen for updates to the template.activeTemplate data in our @wordpress/data store
 * and update the select box value based on this change. Also, listen for changes
 * to our select box and update the store when needed.
 *
 * @param templateField
 * @since 4.1
 */
export function activeTemplateStoreListener(
	templateField: HTMLSelectElement
): () => void {
	let prevActiveTemplate = select(
		TEMPLATE_STORE_NAME
	).getActiveTemplate() as string;

	/* Watch store for changes */
	const unsubscribeActive = subscribe(() => {
		const activeTemplate = select(
			TEMPLATE_STORE_NAME
		).getActiveTemplate() as string;
		if (activeTemplate !== prevActiveTemplate) {
			prevActiveTemplate = activeTemplate;
			if (templateField.value !== activeTemplate) {
				templateField.value = activeTemplate;
				templateField.dispatchEvent(new Event('change'));
			}
		}
	}, TEMPLATE_STORE_NAME);

	/* Watch DOM for changes */
	templateField.addEventListener('change', () => {
		const activeTemplate = select(
			TEMPLATE_STORE_NAME
		).getActiveTemplate() as string;
		if (templateField.value !== activeTemplate) {
			void dispatch(templateStore).selectTemplate(templateField.value);
		}
	});

	return unsubscribeActive;
}

/**
 * PHP builds the Select box DOM for the templates and when we add or delete a template we need to
 * rebuild this. Instead of duplicating the code on both server and client side we do an AJAX call to
 * get the new select box HTML when the template.list length changes and update the DOM accordingly.
 *
 * @param templateField
 * @since 4.1
 */
export function templateChangeStoreListener(
	templateField: HTMLSelectElement
): () => void {
	let prevListLength = (select(TEMPLATE_STORE_NAME).getList() as unknown[])
		.length;
	let prevSelectBoxText = select(
		TEMPLATE_STORE_NAME
	).getUpdateSelectBoxText() as string;

	const unsubscribeChange = subscribe(() => {
		const list = select(TEMPLATE_STORE_NAME).getList() as unknown[];
		const updateSelectBoxText = select(
			TEMPLATE_STORE_NAME
		).getUpdateSelectBoxText() as string;

		if (list.length !== prevListLength) {
			prevListLength = list.length;
			void dispatch(templateStore).updateSelectBox();
		}

		if (updateSelectBoxText && updateSelectBoxText !== prevSelectBoxText) {
			prevSelectBoxText = updateSelectBoxText;
			templateField.innerHTML = updateSelectBoxText;
			templateField.value = select(
				TEMPLATE_STORE_NAME
			).getActiveTemplate() as string;
			templateField.dispatchEvent(new CustomEvent('chosen:updated'));
		}
	}, TEMPLATE_STORE_NAME);

	return unsubscribeChange;
}
